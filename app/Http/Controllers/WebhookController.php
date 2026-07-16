<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\AutomationLog;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Services\AiReplyService;
use App\Services\ConversationMessageService;
use App\Services\GmailConnectionService;
use App\Services\MessageIngestionService;
use App\Services\MetaConnectionService;
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

    public function verifyMeta(Request $request)
    {
        $mode = $request->query('hub.mode', $request->query('hub_mode'));
        $verifyToken = $request->query('hub.verify_token', $request->query('hub_verify_token'));
        $challenge = $request->query('hub.challenge', $request->query('hub_challenge'));

        abort_unless($mode === 'subscribe', 403);
        abort_unless(hash_equals((string) config('services.meta.webhook_verify_token'), (string) $verifyToken), 403);

        return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function meta(Request $request, MessageIngestionService $messageIngestionService)
    {
        $appSecret = (string) config('services.meta.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');
        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        abort_unless($appSecret !== '' && hash_equals($expectedSignature, $signature), 401);

        $payload = $request->all();

        if (in_array($payload['object'] ?? null, ['page', 'instagram'], true)) {
            $platform = ($payload['object'] ?? null) === 'instagram' ? 'Instagram' : 'Facebook';

            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['messaging'] ?? [] as $event) {
                    $message = $event['message'] ?? [];
                    if (($message['is_echo'] ?? false) || empty($message['text']) || empty($event['sender']['id'])) {
                        continue;
                    }

                    $assetId = (string) ($event['recipient']['id'] ?? $entry['id'] ?? '');
                    $account = ConnectedAccount::where('platform', $platform)
                        ->where('status', 'connected')
                        ->where(function ($query) use ($assetId) {
                            $query->where('page_id', $assetId)->orWhere('external_account_id', $assetId);
                        })
                        ->first();

                    if (! $account) {
                        continue;
                    }

                    $senderId = (string) $event['sender']['id'];
                    $conversation = $messageIngestionService->ingest([
                        'business_id' => $account->business_id,
                        'channel' => $platform,
                        'connected_account_id' => $account->id,
                        'external_account_id' => $account->external_account_id,
                        'customer_name' => $platform.' customer',
                        'customer_external_id' => $senderId,
                        'body' => (string) $message['text'],
                        'metadata' => [
                            'source' => $platform === 'Instagram' ? 'meta_instagram' : 'meta_messenger',
                            'meta_message_id' => $message['mid'] ?? null,
                            'meta_timestamp' => $event['timestamp'] ?? null,
                        ],
                    ]);

                    AutomationLog::create([
                        'business_id' => $account->business_id,
                        'connected_account_id' => $account->id,
                        'event_type' => 'meta_webhook',
                        'status' => 'success',
                        'message' => $platform.' message processed.',
                        'metadata' => ['conversation_id' => $conversation->id, 'message_id' => $message['mid'] ?? null],
                    ]);
                }
            }

            return response()->json(['message' => 'Meta webhook processed.']);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                $account = ConnectedAccount::where('platform', 'WhatsApp')
                    ->where('phone_number_id', (string) ($value['metadata']['phone_number_id'] ?? ''))
                    ->where('status', 'connected')
                    ->first();

                if (! $account) {
                    continue;
                }

                foreach ($value['messages'] ?? [] as $message) {
                    if (($message['type'] ?? null) !== 'text') {
                        continue;
                    }

                    $contact = collect($value['contacts'] ?? [])->firstWhere('wa_id', $message['from']);
                    $conversation = $messageIngestionService->ingest([
                        'business_id' => $account->business_id,
                        'channel' => 'WhatsApp',
                        'connected_account_id' => $account->id,
                        'external_account_id' => $account->external_account_id,
                        'customer_name' => $contact['profile']['name'] ?? $message['from'],
                        'customer_external_id' => $message['from'],
                        'body' => $message['text']['body'] ?? '',
                        'metadata' => ['source' => 'meta_whatsapp', 'whatsapp_message_id' => $message['id'] ?? null],
                    ]);

                    AutomationLog::create([
                        'business_id' => $account->business_id,
                        'connected_account_id' => $account->id,
                        'event_type' => 'meta_webhook',
                        'status' => 'success',
                        'message' => 'WhatsApp message processed.',
                        'metadata' => ['conversation_id' => $conversation->id, 'message_id' => $message['id'] ?? null],
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Meta webhook processed.']);
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

        $this->updateTelegramWebhookMeta($account, [
            'last_webhook_attempt_at' => now()->toIso8601String(),
            'last_webhook_update_id' => $request->input('update_id'),
            'last_webhook_error' => null,
        ]);

        $expectedSecret = $account->provider_meta['webhook_secret'] ?? null;
        $providedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (
            ! is_string($expectedSecret)
            || $expectedSecret === ''
            || ! hash_equals($expectedSecret, (string) $providedSecret)
        ) {
            $this->recordTelegramWebhookFailure($account, 'Telegram webhook secret mismatch.', [
                'update_id' => $request->input('update_id'),
                'has_secret_header' => $providedSecret !== null,
            ]);

            abort(401);
        }

        $payload = $telegramConnectionService->normalizeUpdate($account, $request->all());

        if (! $payload) {
            $this->recordTelegramWebhookFailure($account, 'Telegram update ignored because it did not contain a supported message.', [
                'update_id' => $request->input('update_id'),
                'keys' => array_keys($request->all()),
            ], 'ignored');

            return response()->json(['message' => 'Telegram update ignored.']);
        }

        $payload = $this->withTelegramCustomerAvatar($account, $payload, $telegramConnectionService);

        $conversation = $messageIngestionService->ingest($payload + ['source' => 'telegram']);

        $this->updateTelegramWebhookMeta($account, [
            'last_webhook_processed_at' => now()->toIso8601String(),
            'last_webhook_error' => null,
        ]);

        AutomationLog::create([
            'business_id' => $account->business_id,
            'connected_account_id' => $account->id,
            'event_type' => 'telegram_webhook',
            'status' => 'success',
            'message' => 'Telegram message processed.',
            'metadata' => [
                'update_id' => $request->input('update_id'),
                'conversation_id' => $conversation->id,
                'telegram_chat_id' => $payload['metadata']['telegram_chat_id'] ?? null,
            ],
        ]);

        return response()->json([
            'message' => 'Telegram message processed.',
            'conversation_id' => $conversation->id,
            'state' => $conversation->status,
        ]);
    }

    public function gmailPubSub(Request $request, GmailConnectionService $gmailConnectionService)
    {
        $expectedToken = config('services.gmail.pubsub_verification_token');
        $providedToken = $request->query('token') ?: $request->header('X-GMAIL-PUBSUB-TOKEN');

        abort_unless(
            is_string($expectedToken)
            && $expectedToken !== ''
            && hash_equals($expectedToken, (string) $providedToken),
            401
        );

        $result = $gmailConnectionService->syncFromPubSubNotification($request->all());

        return response()->json($result);
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

    private function recordTelegramWebhookFailure(ConnectedAccount $account, string $message, array $metadata = [], string $status = 'failed'): void
    {
        $this->updateTelegramWebhookMeta($account, [
            'last_webhook_error' => $message,
            'last_webhook_error_at' => now()->toIso8601String(),
        ]);

        AutomationLog::create([
            'business_id' => $account->business_id,
            'connected_account_id' => $account->id,
            'event_type' => 'telegram_webhook',
            'status' => $status,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    private function updateTelegramWebhookMeta(ConnectedAccount $account, array $meta): void
    {
        $account->forceFill([
            'provider_meta' => array_merge($account->provider_meta ?? [], $meta),
        ])->save();
    }

    private function withTelegramCustomerAvatar(
        ConnectedAccount $account,
        array $payload,
        TelegramConnectionService $telegramConnectionService
    ): array {
        $customerExternalId = (string) ($payload['customer_external_id'] ?? '');

        if ($customerExternalId === '') {
            return $payload;
        }

        $existingCustomer = Customer::where('business_id', $account->business_id)
            ->where('channel', 'Telegram')
            ->where('external_id', $customerExternalId)
            ->first();

        if ($existingCustomer?->avatar_path || $existingCustomer?->avatar_url) {
            return $payload;
        }

        try {
            $avatar = $telegramConnectionService->fetchCustomerAvatar($account, $customerExternalId);
        } catch (\Throwable $exception) {
            report($exception);

            $avatar = null;
        }

        if ($avatar) {
            $payload['customer_avatar'] = $avatar;
        }

        return $payload;
    }
}
