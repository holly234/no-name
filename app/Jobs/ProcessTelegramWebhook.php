<?php

namespace App\Jobs;

use App\Models\AutomationLog;
use App\Models\ConnectedAccount;
use App\Models\Customer;
use App\Services\MessageIngestionService;
use App\Services\TelegramConnectionService;
use App\Support\QueueName;
use App\Support\ProviderError;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTelegramWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $accountId,
        public readonly array $payload
    ) {
        $this->onQueue(QueueName::WEBHOOKS);
    }

    public function handle(
        TelegramConnectionService $telegramConnectionService,
        MessageIngestionService $messageIngestionService
    ): array {
        $account = ConnectedAccount::query()
            ->where('platform', 'Telegram')
            ->where('status', 'connected')
            ->find($this->accountId);

        if (! $account) {
            return ['status' => 'ignored'];
        }

        $normalized = $telegramConnectionService->normalizeUpdate($account, $this->payload);

        if (! $normalized) {
            $this->recordFailure($account, 'Telegram update ignored because it did not contain a supported message.', [
                'update_id' => $this->payload['update_id'] ?? null,
                'keys' => array_keys($this->payload),
            ], 'ignored');

            return ['status' => 'ignored'];
        }

        $normalized = $this->withTelegramCustomerAvatar($account, $normalized, $telegramConnectionService);
        $conversation = $messageIngestionService->ingest($normalized + ['source' => 'telegram']);

        $this->updateWebhookMeta($account, [
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
                'update_id' => $this->payload['update_id'] ?? null,
                'conversation_id' => $conversation->id,
                'telegram_chat_id' => $normalized['metadata']['telegram_chat_id'] ?? null,
            ],
        ]);

        return [
            'status' => 'processed',
            'conversation_id' => $conversation->id,
            'state' => $conversation->status,
        ];
    }

    private function recordFailure(ConnectedAccount $account, string $message, array $metadata = [], string $status = 'failed'): void
    {
        $this->updateWebhookMeta($account, [
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

    private function updateWebhookMeta(ConnectedAccount $account, array $meta): void
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
            ProviderError::report($exception, ['provider' => 'telegram', 'account_id' => $account->id]);

            $avatar = null;
        }

        if ($avatar) {
            $payload['customer_avatar'] = $avatar;
        }

        return $payload;
    }
}
