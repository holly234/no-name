<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessGmailPubSubNotification;
use App\Jobs\ProcessIncomingMessageWebhook;
use App\Jobs\ProcessMetaWebhook;
use App\Jobs\ProcessTelegramWebhook;
use App\Models\AutomationLog;
use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Services\AiReplyService;
use App\Services\ConversationMessageService;
use App\Services\TelegramConnectionService;
use App\Support\QueueDispatch;
use App\Support\ProviderError;
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
        $expectedToken = (string) config('services.meta.webhook_verify_token');
        abort_unless($expectedToken !== '' && hash_equals($expectedToken, (string) $verifyToken), 403);

        return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function meta(Request $request)
    {
        $appSecret = (string) config('services.meta.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');
        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        abort_unless($appSecret !== '' && hash_equals($expectedSignature, $signature), 401);

        $payload = $request->all();
        $this->dispatchQueueAware(new ProcessMetaWebhook($payload));

        return response()->json(['message' => 'Meta webhook processed.']);
    }

    public function incomingMessage(Request $request)
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

        $result = $this->dispatchQueueAware(new ProcessIncomingMessageWebhook($validated));

        return response()->json(array_filter([
            'message' => 'Incoming message processed.',
            'conversation_id' => $result['conversation_id'] ?? null,
            'state' => $result['state'] ?? null,
        ], fn ($value) => $value !== null));
    }

    public function telegram(
        Request $request,
        ConnectedAccount $account
    ) {
        abort_unless($account->platform === 'Telegram' && $account->status === 'connected', 404);

        $expectedSecret = $account->provider_meta['webhook_secret'] ?? null;
        $providedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (
            ! is_string($expectedSecret)
            || $expectedSecret === ''
            || ! hash_equals($expectedSecret, (string) $providedSecret)
        ) {
            abort(401);
        }

        $this->updateTelegramWebhookMeta($account, [
            'last_webhook_attempt_at' => now()->toIso8601String(),
            'last_webhook_update_id' => $request->input('update_id'),
            'last_webhook_error' => null,
        ]);

        $result = $this->dispatchQueueAware(new ProcessTelegramWebhook($account->id, $request->all()));

        if (($result['status'] ?? null) === 'ignored') {
            return response()->json(['message' => 'Telegram update ignored.']);
        }

        return response()->json(array_filter([
            'message' => 'Telegram message processed.',
            'conversation_id' => $result['conversation_id'] ?? null,
            'state' => $result['state'] ?? null,
        ], fn ($value) => $value !== null));
    }

    public function gmailPubSub(Request $request)
    {
        $expectedToken = config('services.gmail.pubsub_verification_token');
        $providedToken = $request->query('token') ?: $request->header('X-GMAIL-PUBSUB-TOKEN');

        abort_unless(
            is_string($expectedToken)
            && $expectedToken !== ''
            && hash_equals($expectedToken, (string) $providedToken),
            401
        );

        $result = $this->dispatchQueueAware(new ProcessGmailPubSubNotification($request->all()));

        return response()->json($result ?: [
            'status' => 'queued',
            'message' => 'Gmail Pub/Sub notification queued.',
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
            ProviderError::report($exception, ['provider' => 'telegram']);

            $avatar = null;
        }

        if ($avatar) {
            $payload['customer_avatar'] = $avatar;
        }

        return $payload;
    }

    private function dispatchQueueAware(object $job): mixed
    {
        return QueueDispatch::dispatch($job);
    }
}
