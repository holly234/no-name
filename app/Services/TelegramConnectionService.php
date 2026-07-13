<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\MessageAttachment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TelegramConnectionService
{
    private const MAX_ATTACHMENT_BYTES = 10485760;

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
            ]);
    }

    public function webhookUrl(ConnectedAccount $account): string
    {
        return rtrim((string) config('app.url'), '/').'/api/webhooks/telegram/'.$account->id;
    }

    public function sendTextMessage(Conversation $conversation, string $body): ?array
    {
        if ($conversation->channel !== 'Telegram' || $body === '') {
            return null;
        }

        $account = $conversation->connectedAccount;

        if (! $account || $account->platform !== 'Telegram') {
            throw new \RuntimeException('This conversation is not linked to a Telegram account.');
        }

        if ($account->status !== 'connected') {
            throw new \RuntimeException('The Telegram account for this conversation is disconnected.');
        }

        if (! $account->access_token) {
            throw new \RuntimeException('The Telegram account for this conversation has no bot token.');
        }

        $response = Http::timeout(15)
            ->asJson()
            ->post($this->endpoint($account, 'sendMessage'), [
                'chat_id' => $conversation->customer_external_id,
                'text' => $body,
                'disable_web_page_preview' => false,
            ])
            ->throw()
            ->json();

        if (! (bool) ($response['ok'] ?? false)) {
            throw new \RuntimeException($response['description'] ?? 'Telegram did not confirm the reply was sent.');
        }

        return $response;
    }

    public function sendAttachment(Conversation $conversation, MessageAttachment $attachment): ?array
    {
        if ($conversation->channel !== 'Telegram') {
            return null;
        }

        $account = $conversation->connectedAccount;

        if (! $account || $account->platform !== 'Telegram') {
            throw new \RuntimeException('This conversation is not linked to a Telegram account.');
        }

        if ($account->status !== 'connected') {
            throw new \RuntimeException('The Telegram account for this conversation is disconnected.');
        }

        if (! $account->access_token) {
            throw new \RuntimeException('The Telegram account for this conversation has no bot token.');
        }

        if (! Storage::disk($attachment->disk)->exists($attachment->storage_path)) {
            throw new \RuntimeException('The selected attachment could not be found.');
        }

        if ((int) $attachment->size > self::MAX_ATTACHMENT_BYTES) {
            throw new \RuntimeException('Telegram attachments must be 10MB or smaller.');
        }

        $mimeType = (string) $attachment->mime_type;
        $isVoiceNote = ($attachment->metadata['media_type'] ?? null) === 'voice'
            || Str::startsWith($attachment->filename, 'voice-note-');

        [$method, $field] = match (true) {
            str_starts_with($mimeType, 'image/') => ['sendPhoto', 'photo'],
            $isVoiceNote => ['sendVoice', 'voice'],
            str_starts_with($mimeType, 'video/') => ['sendVideo', 'video'],
            str_starts_with($mimeType, 'audio/') => ['sendVoice', 'voice'],
            default => ['sendDocument', 'document'],
        };

        $response = Http::timeout(30)
            ->attach(
                $field,
                Storage::disk($attachment->disk)->get($attachment->storage_path),
                $attachment->filename
            )
            ->post($this->endpoint($account, $method), [
                'chat_id' => $conversation->customer_external_id,
            ])
            ->throw()
            ->json();

        if (! (bool) ($response['ok'] ?? false)) {
            throw new \RuntimeException($response['description'] ?? 'Telegram did not confirm the attachment was sent.');
        }

        return $response;
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
            'connected_account_id' => $account->id,
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
            'attachments' => $this->mediaAttachments($account, $message),
        ];
    }

    public function fetchCustomerAvatar(ConnectedAccount $account, string $telegramUserId): ?array
    {
        if ($account->platform !== 'Telegram' || ! $account->access_token || $telegramUserId === '') {
            return null;
        }

        $profilePhotos = Http::timeout(8)
            ->get($this->endpoint($account, 'getUserProfilePhotos'), [
                'user_id' => $telegramUserId,
                'limit' => 1,
            ])
            ->throw()
            ->json();

        $photoSizes = $profilePhotos['result']['photos'][0] ?? null;

        if (! (bool) ($profilePhotos['ok'] ?? false) || ! is_array($photoSizes) || $photoSizes === []) {
            return null;
        }

        $largestPhoto = collect($photoSizes)
            ->sortByDesc(fn (array $photo) => (int) ($photo['file_size'] ?? (($photo['width'] ?? 0) * ($photo['height'] ?? 0))))
            ->first();

        $fileId = $largestPhoto['file_id'] ?? null;
        $fileUniqueId = $largestPhoto['file_unique_id'] ?? $fileId;

        if (! $fileId) {
            return null;
        }

        $file = Http::timeout(8)
            ->get($this->endpoint($account, 'getFile'), ['file_id' => $fileId])
            ->throw()
            ->json();

        $filePath = $file['result']['file_path'] ?? null;

        if (! (bool) ($file['ok'] ?? false) || ! $filePath) {
            return null;
        }

        $download = Http::timeout(15)
            ->get($this->fileEndpoint($account, $filePath))
            ->throw();

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
        $storagePath = 'customer-avatars/'.$account->business_id.'/'.$account->id.'/'.Str::slug($telegramUserId).'-'.Str::slug((string) $fileUniqueId).'.'.$extension;

        Storage::disk('public')->put($storagePath, $download->body());

        return [
            'avatar_disk' => 'public',
            'avatar_path' => $storagePath,
            'avatar_provider_id' => (string) $fileUniqueId,
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
            isset($message['video']) => '[Video]',
            isset($message['voice']) => '[Voice note]',
            isset($message['audio']) => '[Audio]',
            default => null,
        };
    }

    private function mediaAttachments(ConnectedAccount $account, array $message): array
    {
        $media = $this->telegramMedia($message);

        if (! $media) {
            return [];
        }

        $fileId = $media['file_id'] ?? null;
        $fileUniqueId = $media['file_unique_id'] ?? $fileId;
        $fileSize = (int) ($media['file_size'] ?? 0);

        if (! $fileId || ($fileSize > 0 && $fileSize > self::MAX_ATTACHMENT_BYTES)) {
            return [];
        }

        try {
            $file = Http::timeout(8)
                ->get($this->endpoint($account, 'getFile'), ['file_id' => $fileId])
                ->throw()
                ->json();

            $filePath = $file['result']['file_path'] ?? null;

            if (! (bool) ($file['ok'] ?? false) || ! $filePath) {
                return [];
            }

            $download = Http::timeout(30)
                ->get($this->fileEndpoint($account, $filePath))
                ->throw();
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }

        $body = $download->body();
        $downloadedSize = strlen($body);

        if ($downloadedSize > self::MAX_ATTACHMENT_BYTES) {
            return [];
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: $media['extension'];
        $messageId = (string) ($message['message_id'] ?? Str::uuid());
        $storagePath = 'telegram-attachments/'.$account->business_id.'/'.$account->id.'/'.$messageId.'-'.Str::slug((string) $fileUniqueId).'.'.$extension;

        Storage::disk('local')->put($storagePath, $body);

        return [[
            'provider' => 'telegram',
            'provider_attachment_id' => (string) $fileUniqueId,
            'filename' => $media['filename'] ?: 'telegram-'.$media['type'].'-'.$messageId.'.'.$extension,
            'mime_type' => $media['mime_type'],
            'size' => $fileSize > 0 ? $fileSize : $downloadedSize,
            'disk' => 'local',
            'storage_path' => $storagePath,
            'metadata' => [
                'telegram_file_id' => $fileId,
                'telegram_file_path' => $filePath,
                'media_type' => $media['type'],
            ],
        ]];
    }

    private function telegramMedia(array $message): ?array
    {
        if (isset($message['photo']) && is_array($message['photo']) && $message['photo'] !== []) {
            $photo = collect($message['photo'])
                ->sortByDesc(fn (array $item) => (int) ($item['file_size'] ?? (($item['width'] ?? 0) * ($item['height'] ?? 0))))
                ->first();

            return [
                ...$photo,
                'type' => 'photo',
                'filename' => null,
                'mime_type' => 'image/jpeg',
                'extension' => 'jpg',
            ];
        }

        if (isset($message['voice']) && is_array($message['voice'])) {
            return [
                ...$message['voice'],
                'type' => 'voice',
                'filename' => null,
                'mime_type' => $message['voice']['mime_type'] ?? 'audio/ogg',
                'extension' => 'ogg',
            ];
        }

        if (isset($message['audio']) && is_array($message['audio'])) {
            return [
                ...$message['audio'],
                'type' => 'audio',
                'filename' => $message['audio']['file_name'] ?? null,
                'mime_type' => $message['audio']['mime_type'] ?? 'audio/mpeg',
                'extension' => 'mp3',
            ];
        }

        if (isset($message['video']) && is_array($message['video'])) {
            return [
                ...$message['video'],
                'type' => 'video',
                'filename' => $message['video']['file_name'] ?? null,
                'mime_type' => $message['video']['mime_type'] ?? 'video/mp4',
                'extension' => pathinfo((string) ($message['video']['file_name'] ?? ''), PATHINFO_EXTENSION) ?: 'mp4',
            ];
        }

        if (isset($message['document']) && is_array($message['document'])) {
            $mimeType = (string) ($message['document']['mime_type'] ?? '');

            if (! str_starts_with($mimeType, 'image/') && ! str_starts_with($mimeType, 'video/')) {
                return null;
            }

            $type = str_starts_with($mimeType, 'video/') ? 'video' : 'image';

            return [
                ...$message['document'],
                'type' => $type,
                'filename' => $message['document']['file_name'] ?? null,
                'mime_type' => $mimeType,
                'extension' => pathinfo((string) ($message['document']['file_name'] ?? ''), PATHINFO_EXTENSION) ?: ($type === 'video' ? 'mp4' : 'jpg'),
            ];
        }

        return null;
    }

    private function endpoint(ConnectedAccount $account, string $method): string
    {
        return rtrim((string) config('services.telegram.api_base'), '/').$account->access_token.'/'.$method;
    }

    private function fileEndpoint(ConnectedAccount $account, string $filePath): string
    {
        $apiBase = rtrim((string) config('services.telegram.api_base'), '/');
        $fileBase = str_replace('/bot', '/file/bot', $apiBase);

        return $fileBase.$account->access_token.'/'.$filePath;
    }

    private function storeWebhookMeta(ConnectedAccount $account, array $meta): void
    {
        $account->forceFill([
            'provider_meta' => array_merge($account->provider_meta ?? [], $meta),
        ])->save();
    }
}
