<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TelegramConnectionService
{
    public function registerWebhook(ConnectedAccount $account): array
    {
        if ($account->platform !== 'Telegram' || ! $account->access_token) {
            return [
                'ok' => false,
                'status' => 'missing_token',
                'message' => 'Telegram bot token is missing.',
            ];
        }

        $webhookUrl = $this->webhookUrl($account);

        if (! Str::startsWith($webhookUrl, 'https://')) {
            $this->storeWebhookMeta($account, [
                'webhook_url' => $webhookUrl,
                'webhook_status' => 'needs_public_https',
            ]);

            return [
                'ok' => false,
                'status' => 'needs_public_https',
                'message' => 'Telegram connected, but real-time messages need a public HTTPS APP_URL or tunnel.',
            ];
        }

        $response = Http::timeout(15)
            ->asJson()
            ->post($this->endpoint($account, 'setWebhook'), [
                'url' => $webhookUrl,
                'secret_token' => $account->provider_meta['webhook_secret'] ?? '',
                'allowed_updates' => ['message', 'edited_message', 'channel_post'],
                'drop_pending_updates' => false,
            ])
            ->throw()
            ->json();

        $isRegistered = (bool) ($response['ok'] ?? false);

        $this->storeWebhookMeta($account, [
            'webhook_url' => $webhookUrl,
            'webhook_status' => $isRegistered ? 'active' : 'failed',
            'webhook_last_response' => $response,
        ]);

        return [
            'ok' => $isRegistered,
            'status' => $isRegistered ? 'active' : 'failed',
            'message' => $isRegistered
                ? 'Telegram account connected and webhook is live.'
                : 'Telegram connected, but webhook registration failed.',
        ];
    }

    public function forgetWebhook(ConnectedAccount $account): void
    {
        if ($account->platform !== 'Telegram' || ! $account->access_token) {
            return;
        }

        Http::timeout(10)
            ->asJson()
            ->post($this->endpoint($account, 'deleteWebhook'), [
                'drop_pending_updates' => false,
            ])
            ->throw();
    }

    public function webhookUrl(ConnectedAccount $account): string
    {
        return rtrim((string) config('app.url'), '/').'/api/webhooks/telegram/'.$account->id;
    }

    public function sendTextMessage(Conversation $conversation, string $body): void
    {
        if ($conversation->channel !== 'Telegram' || $body === '') {
            return;
        }

        $account = $conversation->connectedAccount;

        if (! $account || $account->platform !== 'Telegram' || ! $account->access_token) {
            return;
        }

        Http::timeout(15)
            ->asJson()
            ->post($this->endpoint($account, 'sendMessage'), [
                'chat_id' => $conversation->customer_external_id,
                'text' => $body,
                'disable_web_page_preview' => false,
            ])
            ->throw();
    }

    public function normalizeUpdate(ConnectedAccount $account, array $update): ?array
    {
        $message = $update['message']
            ?? $update['edited_message']
            ?? $update['channel_post']
            ?? null;

        if (! is_array($message)) {
            return null;
        }

        $chat = $message['chat'] ?? [];
        $from = $message['from'] ?? $chat;
        $chatId = $chat['id'] ?? null;
        $body = $message['text']
            ?? $message['caption']
            ?? $this->mediaPlaceholder($message)
            ?? null;

        if (! $chatId || ! is_string($body) || trim($body) === '') {
            return null;
        }

        $username = $from['username'] ?? $chat['username'] ?? null;
        $name = trim(implode(' ', array_filter([
            $from['first_name'] ?? $chat['first_name'] ?? null,
            $from['last_name'] ?? $chat['last_name'] ?? null,
        ])));

        return [
            'business_id' => $account->business_id,
            'channel' => 'Telegram',
            'external_account_id' => $account->external_account_id,
            'customer_name' => $name !== '' ? $name : ($username ? '@'.$username : 'Telegram Customer'),
            'customer_external_id' => (string) $chatId,
            'body' => trim($body),
            'metadata' => [
                'source' => 'telegram',
                'telegram_update_id' => $update['update_id'] ?? null,
                'telegram_message_id' => $message['message_id'] ?? null,
                'telegram_chat_id' => (string) $chatId,
                'telegram_username' => $username ? '@'.$username : null,
            ],
        ];
    }

    public function isConnectionError(\Throwable $exception): bool
    {
        return $exception instanceof ConnectionException;
    }

    private function mediaPlaceholder(array $message): ?string
    {
        return match (true) {
            isset($message['photo']) => '[Photo]',
            isset($message['document']) => '[Document]',
            isset($message['voice']) => '[Voice note]',
            isset($message['audio']) => '[Audio]',
            default => null,
        };
    }

    private function endpoint(ConnectedAccount $account, string $method): string
    {
        return rtrim((string) config('services.telegram.api_base'), '/').$account->access_token.'/'.$method;
    }

    private function storeWebhookMeta(ConnectedAccount $account, array $meta): void
    {
        $account->forceFill([
            'provider_meta' => array_merge($account->provider_meta ?? [], $meta),
        ])->save();
    }
}
