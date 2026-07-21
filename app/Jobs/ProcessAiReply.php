<?php

namespace App\Jobs;

use App\Contracts\AiProvider;
use App\Exceptions\InsufficientAiCredits;
use App\Models\AiSetting;
use App\Models\AiUsageRecord;
use App\Models\AutomationLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AiCreditLedgerService;
use App\Services\AiPromptBuilder;
use App\Services\ConversationMessageService;
use App\Services\GeminiVoiceTranscriptionService;
use App\Services\OutboundChannelService;
use App\Support\AiReplyFormatter;
use App\Support\QueueName;
use App\Support\ProviderError;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessAiReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public readonly int $messageId,
        public readonly bool $recovery = false,
    )
    {
        $this->onQueue(QueueName::AI);
    }

    public function handle(
        AiProvider $provider,
        AiPromptBuilder $promptBuilder,
        AiCreditLedgerService $credits,
        OutboundChannelService $outbound,
        ConversationMessageService $messages,
        GeminiVoiceTranscriptionService $voiceTranscription,
    ): void {
        $incoming = Message::with('conversation.business')->find($this->messageId);

        if (! $incoming || $incoming->direction !== 'incoming') {
            return;
        }

        $conversation = $incoming->conversation;
        $business = $conversation->business;

        $recoverableHandover = $this->recovery && $conversation->status === Conversation::STATE_NEEDS_HUMAN;

        if ($conversation->ai_mode !== 'auto'
            || ($conversation->status !== Conversation::STATE_AI_HANDLING && ! $recoverableHandover)) {
            return;
        }

        if (! $this->recovery && AiUsageRecord::where('message_id', $incoming->id)->where('status', 'completed')->exists()) {
            return;
        }

        try {
            $reservation = $credits->reserve(
                $business,
                (int) config('ai.reservation_credits', 25),
                ['conversation_id' => $conversation->id, 'message_id' => $incoming->id]
            );
        } catch (InsufficientAiCredits) {
            $conversation->update(['status' => Conversation::STATE_NEEDS_HUMAN]);
            $this->log($conversation, 'ai_reply_blocked', 'AI reply paused because the workspace has insufficient credits.');

            return;
        }

        $usage = AiUsageRecord::create([
            'business_id' => $business->id,
            'conversation_id' => $conversation->id,
            'message_id' => $incoming->id,
            'provider' => (string) config('ai.provider'),
            'model' => (string) config('ai.providers.'.config('ai.provider').'.model'),
            'status' => 'processing',
            'metadata' => ['reservation_reference' => $reservation->reference],
        ]);

        try {
            $incomingText = (string) ($incoming->metadata['transcription'] ?? $incoming->body);

            if (! isset($incoming->metadata['transcription'])) {
                $transcription = $voiceTranscription->transcribe($incoming);

                if ($transcription !== null) {
                    $incoming->update([
                        'metadata' => array_merge($incoming->metadata ?? [], ['transcription' => $transcription]),
                    ]);
                    $incomingText = $transcription;
                }
            }

            $generation = $provider->generate($promptBuilder->build($conversation, $incomingText));
            $actualCredits = $credits->creditsForTokens($generation->inputTokens, $generation->outputTokens);

            if ($generation->requiresHuman || $generation->state === Conversation::STATE_NEEDS_HUMAN || $generation->reply === '') {
                $handoverReply = trim($generation->reply)
                    ?: trim((string) AiSetting::where('business_id', $business->id)->value('fallback_response'))
                    ?: 'Thanks for the details. I’m passing this to a team member who can confirm that for you. They’ll reply here shortly.';

                try {
                    $delivery = $outbound->sendText($conversation->fresh('connectedAccount'), $handoverReply);
                } catch (Throwable $deliveryException) {
                    $credits->release($business, $reservation, 'outbound_delivery_failed');
                    $conversation->update(['status' => Conversation::STATE_NEEDS_HUMAN]);
                    $this->completeUsage($usage, $generation, 0, [
                        'outcome' => 'handover_delivery_failed',
                        'error' => ProviderError::message($deliveryException),
                    ], 'delivery_failed');
                    $this->log($conversation, 'ai_handover_delivery_failed', 'The conversation was handed over, but its acknowledgement could not be delivered.');
                    ProviderError::report($deliveryException, ['provider' => 'outbound']);

                    return;
                }

                $messages->saveOutgoing($conversation, $handoverReply, 'ai', [
                    'provider' => $generation->provider,
                    'model' => $generation->model,
                    'confidence' => $generation->confidence,
                    'intent' => $generation->intent,
                    'handover' => true,
                    'delivery' => $delivery,
                ]);
                $charged = $credits->settle($business, $reservation, $actualCredits, ['outcome' => 'escalated']);
                $conversation->update(['status' => Conversation::STATE_NEEDS_HUMAN]);
                $this->completeUsage($usage, $generation, $charged, ['outcome' => 'escalated']);
                $this->log($conversation, 'ai_reply_escalated', $generation->reason ?: 'AI escalated the conversation to staff.');

                return;
            }

            $segments = AiReplyFormatter::segments($generation->reply, $conversation->channel);
            $delivered = 0;

            foreach ($segments as $index => $segment) {
                try {
                    $delivery = $outbound->sendText($conversation->fresh('connectedAccount'), $segment);
                } catch (Throwable $deliveryException) {
                    $conversation->update(['status' => Conversation::STATE_NEEDS_HUMAN]);

                    if ($delivered === 0) {
                        $credits->release($business, $reservation, 'outbound_delivery_failed');
                        $charged = 0;
                        $outcome = 'delivery_failed';
                        $logMessage = 'AI reply delivery failed and credits were released.';
                    } else {
                        $charged = $credits->settle($business, $reservation, $actualCredits, ['outcome' => 'partial_delivery_failed']);
                        $outcome = 'partial_delivery_failed';
                        $logMessage = 'Part of the AI reply was delivered before the channel failed. Staff attention is required.';
                    }

                    $this->completeUsage($usage, $generation, $charged, [
                        'outcome' => $outcome,
                        'delivered_segments' => $delivered,
                        'error' => ProviderError::message($deliveryException),
                    ], 'delivery_failed');
                    $this->log($conversation, 'ai_reply_delivery_failed', $logMessage);
                    ProviderError::report($deliveryException, ['provider' => 'outbound']);

                    return;
                }

                $messages->saveOutgoing($conversation, $segment, 'ai', [
                    'provider' => $generation->provider,
                    'model' => $generation->model,
                    'confidence' => $generation->confidence,
                    'intent' => $generation->intent,
                    'segment' => $index + 1,
                    'segment_count' => count($segments),
                    'delivery' => $delivery,
                ]);
                $delivered++;
            }

            $charged = $credits->settle($business, $reservation, $actualCredits, ['outcome' => 'replied']);
            $this->completeUsage($usage, $generation, $charged, [
                'outcome' => 'replied',
                'delivered_segments' => $delivered,
            ]);
            $this->log($conversation, 'ai_reply_sent', 'AI generated and sent a reply.');
        } catch (Throwable $exception) {
            $credits->release($business, $reservation, 'provider_failed');
            $usage->update([
                'status' => 'failed',
                'metadata' => array_merge($usage->metadata ?? [], ['error' => ProviderError::message($exception)]),
            ]);
            ProviderError::report($exception, ['provider' => config('ai.provider')]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $incoming = Message::with('conversation')->find($this->messageId);
        $incoming?->conversation?->update(['status' => Conversation::STATE_NEEDS_HUMAN]);
    }

    private function completeUsage(AiUsageRecord $usage, object $generation, int $charged, array $metadata, string $status = 'completed'): void
    {
        $usage->update([
            'provider' => $generation->provider,
            'model' => $generation->model,
            'input_tokens' => $generation->inputTokens,
            'output_tokens' => $generation->outputTokens,
            'credits_used' => $charged,
            'provider_cost_usd' => $generation->providerCostUsd,
            'latency_ms' => $generation->latencyMs,
            'status' => $status,
            'metadata' => array_merge($usage->metadata ?? [], $generation->metadata, $metadata),
        ]);
    }

    private function log(Conversation $conversation, string $event, string $message): void
    {
        AutomationLog::create([
            'business_id' => $conversation->business_id,
            'connected_account_id' => $conversation->connected_account_id,
            'event_type' => $event,
            'status' => str_contains($event, 'failed') ? 'failed' : 'success',
            'message' => $message,
            'metadata' => ['conversation_id' => $conversation->id],
        ]);
    }
}
