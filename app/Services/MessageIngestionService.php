<?php

namespace App\Services;

use App\Jobs\ProcessAiReply;
use App\Models\AutomationLog;
use App\Models\AiSetting;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Support\QueueDispatch;

class MessageIngestionService
{
    public function __construct(private readonly AiReplyService $aiReplyService)
    {
    }

    public function ingest(array $payload): Conversation
    {
        $businessId = (int) $payload['business_id'];
        $channel = $payload['channel'] ?? 'Instagram';
        $customerName = $payload['customer_name'] ?? 'Demo Customer';
        $customerExternalId = $payload['customer_external_id'] ?? 'demo-customer';
        $body = $payload['body'];
        $accountExternalId = $payload['external_account_id']
            ?? $payload['account_external_id']
            ?? $payload['page_id']
            ?? $payload['phone_number_id']
            ?? strtolower($channel).'-demo-'.$businessId;
        $connectedAccountId = isset($payload['connected_account_id']) ? (int) $payload['connected_account_id'] : null;

        $account = $connectedAccountId
            ? ConnectedAccount::where('business_id', $businessId)
                ->where('platform', $channel)
                ->find($connectedAccountId)
            : null;

        if (! $account) {
            $account = ConnectedAccount::firstOrCreate(
                ['business_id' => $businessId, 'platform' => $channel, 'external_account_id' => $accountExternalId],
                [
                    'account_name' => $channel.' Demo Account',
                    'page_id' => $payload['page_id'] ?? null,
                    'phone_number_id' => $payload['phone_number_id'] ?? null,
                    'status' => 'connected',
                    'connected_at' => now(),
                ]
            );
        }

        $customer = Customer::firstOrCreate(
            ['business_id' => $businessId, 'external_id' => $customerExternalId, 'channel' => $channel],
            ['name' => $customerName, 'tags' => ['demo']]
        );

        if (! empty($payload['customer_avatar']) && is_array($payload['customer_avatar'])) {
            $customer->forceFill(array_filter([
                'avatar_disk' => $payload['customer_avatar']['avatar_disk'] ?? null,
                'avatar_path' => $payload['customer_avatar']['avatar_path'] ?? null,
                'avatar_url' => $payload['customer_avatar']['avatar_url'] ?? null,
                'avatar_provider_id' => $payload['customer_avatar']['avatar_provider_id'] ?? null,
            ]))->save();
        }

        $conversation = Conversation::firstOrCreate(
            [
                'business_id' => $businessId,
                'connected_account_id' => $account->id,
                'customer_external_id' => $customerExternalId,
                'channel' => $channel,
            ],
            [
                'customer_id' => $customer->id,
                'customer_name' => $customerName,
                'status' => Conversation::STATE_AI_HANDLING,
                'ai_mode' => 'auto',
                'last_message_at' => now(),
            ]
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $businessId,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => $body,
            'metadata' => array_merge(
                ['source' => $payload['source'] ?? 'api'],
                $payload['metadata'] ?? []
            ),
        ]);

        foreach (($payload['attachments'] ?? []) as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            if (empty($attachment['provider_attachment_id']) || empty($attachment['storage_path'])) {
                continue;
            }

            MessageAttachment::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'provider' => $attachment['provider'] ?? strtolower($channel),
                    'provider_attachment_id' => $attachment['provider_attachment_id'] ?? null,
                ],
                [
                    'message_id' => $message->id,
                    'filename' => $attachment['filename'] ?? 'attachment',
                    'mime_type' => $attachment['mime_type'] ?? null,
                    'size' => $attachment['size'] ?? null,
                    'disk' => $attachment['disk'] ?? 'local',
                    'storage_path' => $attachment['storage_path'] ?? '',
                    'metadata' => $attachment['metadata'] ?? null,
                ]
            );
        }

        $aiSettings = AiSetting::firstOrCreate(['business_id' => $businessId]);
        if (! $aiSettings->auto_reply_enabled || ! $this->isWithinReplyWindow($aiSettings)) {
            $conversation->update([
                'connected_account_id' => $account->id,
                'customer_id' => $customer->id,
                'customer_name' => $customerName,
                'status' => Conversation::STATE_NEEDS_HUMAN,
                'ai_mode' => 'human',
                'last_message_at' => now(),
            ]);

            AutomationLog::create([
                'business_id' => $businessId,
                'connected_account_id' => $account->id,
                'event_type' => 'incoming_message_received',
                'status' => 'success',
                'message' => $aiSettings->auto_reply_enabled
                    ? 'Incoming message received outside business hours.'
                    : 'Incoming message received while auto replies were disabled.',
                'metadata' => ['conversation_id' => $conversation->id, 'state' => $conversation->fresh()->status],
            ]);

            return $conversation->fresh(['messages']);
        }

        $nextState = $this->aiReplyService->decideState($body, (float) ($payload['confidence'] ?? 0.82));
        $source = (string) ($payload['metadata']['source'] ?? $payload['source'] ?? '');
        $realProviderChannel = in_array($channel, ['Telegram', 'WhatsApp'], true)
            || in_array($source, ['meta_messenger', 'meta_instagram'], true);
        if ($realProviderChannel && ! config('ai.enabled') && $nextState === Conversation::STATE_AI_HANDLING) {
            $nextState = Conversation::STATE_NEEDS_HUMAN;
        }

        $conversation->update([
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => $customerName,
            'status' => $nextState,
            'ai_mode' => $realProviderChannel && $nextState === Conversation::STATE_NEEDS_HUMAN ? 'human' : 'auto',
            'last_message_at' => now(),
        ]);

        if ($nextState === Conversation::STATE_AI_HANDLING) {
            if (config('ai.enabled')) {
                QueueDispatch::dispatch(new ProcessAiReply($message->id));

                return $conversation->fresh(['messages']);
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'business_id' => $businessId,
                'direction' => 'outgoing',
                'sender_type' => 'ai',
                'body' => $this->aiReplyService->generatePlaceholderReply([
                    'business' => $conversation->business,
                    'conversation' => $conversation,
                ]),
                'metadata' => ['confidence' => $payload['confidence'] ?? 0.82],
            ]);

            $conversation->update(['status' => Conversation::STATE_WAITING, 'last_message_at' => now()]);
        }

        AutomationLog::create([
            'business_id' => $businessId,
            'connected_account_id' => $account->id,
            'event_type' => 'incoming_message_received',
            'status' => 'success',
            'message' => 'Incoming message processed by the AI Conversation State Engine.',
            'metadata' => ['conversation_id' => $conversation->id, 'state' => $conversation->fresh()->status],
        ]);

        return $conversation->fresh(['messages']);
    }

    private function isWithinReplyWindow(AiSetting $settings): bool
    {
        if (! $settings->business_hours_enabled) {
            return true;
        }

        $hour = now()->hour;

        return $hour >= 9 && $hour < 19;
    }
}
