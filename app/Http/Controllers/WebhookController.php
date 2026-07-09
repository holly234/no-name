<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Services\AiReplyService;
use App\Services\ConversationMessageService;
use App\Services\MessageIngestionService;
use App\Services\TelegramConnectionService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    private function hasValidSecret(Request $request, ?Business $business = null): bool
    {
        $expected = $business?->webhook_secret ?: config('services.webhooks.secret');
        $provided = $request->header('X-WEBHOOK-SECRET') ?: $request->header('X-META-SECRET');

        return is_string($expected)
            && $expected !== ''
            && hash_equals($expected, (string) $provided);
    }

    public function meta(Request $request, MessageIngestionService $messageIngestionService)
    {
        return $this->incomingMessage($request, $messageIngestionService);
    }

    public function incomingMessage(Request $request, MessageIngestionService $messageIngestionService)
    {
        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'channel' => ['nullable', 'string', 'max:40'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_external_id' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:4000'],
            'confidence' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $business = Business::findOrFail($validated['business_id']);
        abort_unless($this->hasValidSecret($request, $business), 401);

        $conversation = $messageIngestionService->ingest($validated + ['source' => 'webhook']);

        return response()->json([
            'message' => 'Incoming message processed.',
            'conversation_id' => $conversation->id,
            'state' => $conversation->status,
        ]);
    }

    public function telegram(
        Request $request,
        ConnectedAccount $account,
        TelegramConnectionService $telegramConnectionService,
        MessageIngestionService $messageIngestionService
    ) {
        abort_unless($account->platform === 'Telegram' && $account->status === 'connected', 404);

        $expectedSecret = $account->provider_meta['webhook_secret'] ?? null;
        $providedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        abort_unless(
            is_string($expectedSecret)
            && $expectedSecret !== ''
            && hash_equals($expectedSecret, (string) $providedSecret),
            401
        );

        $payload = $telegramConnectionService->normalizeUpdate($account, $request->all());

        if (! $payload) {
            return response()->json(['message' => 'Telegram update ignored.']);
        }

        $conversation = $messageIngestionService->ingest($payload + ['source' => 'telegram']);

        return response()->json([
            'message' => 'Telegram message processed.',
            'conversation_id' => $conversation->id,
            'state' => $conversation->status,
        ]);
    }

    public function generateAiReply(Request $request, AiReplyService $aiReplyService)
    {
        abort_unless($this->hasValidSecret($request), 401);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'confidence' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $state = $aiReplyService->decideState($validated['message'], (float) ($validated['confidence'] ?? 0.82));

        return response()->json([
            'state' => $state,
            'reply' => $state === Conversation::STATE_NEEDS_HUMAN ? null : $aiReplyService->generatePlaceholderReply(),
        ]);
    }

    public function saveOutgoingMessage(Request $request, ConversationMessageService $conversationMessageService)
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'body' => ['required', 'string', 'max:4000'],
            'sender_type' => ['nullable', 'string', 'max:40'],
        ]);

        $conversation = Conversation::with('business')->findOrFail($validated['conversation_id']);
        abort_unless($this->hasValidSecret($request, $conversation->business), 401);

        $message = $conversationMessageService->saveOutgoing(
            $conversation,
            $validated['body'],
            $validated['sender_type'] ?? 'system'
        );

        return response()->json(['message' => 'Outgoing message saved.', 'id' => $message->id]);
    }
}
