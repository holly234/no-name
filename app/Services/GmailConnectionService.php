<?php

namespace App\Services;

use App\Models\AutomationLog;
use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GmailConnectionService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GMAIL_API_BASE = 'https://gmail.googleapis.com/gmail/v1';
    private const HTTP_TIMEOUT_SECONDS = 12;
    private const HTTP_CONNECT_TIMEOUT_SECONDS = 5;

    public function buildRedirectUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.gmail.client_id'),
            'redirect_uri' => config('services.gmail.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes()),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(string $code): array
    {
        $response = $this->googleHttp()->asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.gmail.client_id'),
            'client_secret' => config('services.gmail.client_secret'),
            'redirect_uri' => config('services.gmail.redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Google rejected the Gmail authorization code.');
        }

        return $response->json();
    }

    public function connect(Business $business, array $tokens): ConnectedAccount
    {
        $profile = $this->fetchProfile($tokens['access_token']);
        $email = $profile['emailAddress'] ?? null;

        if (! $email) {
            throw new RuntimeException('Google did not return a Gmail email address.');
        }

        $account = ConnectedAccount::updateOrCreate(
            [
                'business_id' => $business->id,
                'platform' => 'gmail',
                'external_account_id' => $email,
            ],
            [
                'account_name' => $email,
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => isset($tokens['expires_in'])
                    ? now()->addSeconds((int) $tokens['expires_in'])
                    : null,
                'provider_meta' => [
                    'email' => $email,
                    'messages_total' => $profile['messagesTotal'] ?? null,
                    'history_id' => $profile['historyId'] ?? null,
                    'scope' => $tokens['scope'] ?? null,
                ],
                'status' => 'connected',
                'connected_at' => now(),
            ]
        );

        AutomationLog::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'event_type' => 'account_connected',
            'status' => 'success',
            'message' => 'Gmail account connected.',
        ]);

        return $account;
    }

    public function syncRecentInboxMessages(ConnectedAccount $account, int $limit = 20): array
    {
        $token = $this->validAccessToken($account);
        $messageSummaries = $this->gmail($token)
            ->get(self::GMAIL_API_BASE.'/users/me/messages', [
                'maxResults' => $limit,
                'q' => 'in:inbox',
            ])
            ->throw()
            ->json('messages', []);

        $imported = 0;
        $skipped = 0;

        foreach ($messageSummaries as $summary) {
            if (empty($summary['id'])) {
                $skipped++;
                continue;
            }

            $message = $this->gmail($token)
                ->get(self::GMAIL_API_BASE.'/users/me/messages/'.$summary['id'], ['format' => 'full'])
                ->throw()
                ->json();

            if ($this->importGmailMessage($account, $message)) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        AutomationLog::create([
            'business_id' => $account->business_id,
            'connected_account_id' => $account->id,
            'event_type' => 'gmail_sync',
            'status' => 'success',
            'message' => "Gmail sync imported {$imported} messages and skipped {$skipped}.",
            'metadata' => ['imported' => $imported, 'skipped' => $skipped],
        ]);

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    public function sendEmailPlaceholder(): void
    {
        throw new RuntimeException('Gmail sending is intentionally not enabled yet.');
    }

    private function importGmailMessage(ConnectedAccount $account, array $gmailMessage): bool
    {
        $gmailMessageId = $gmailMessage['id'] ?? null;
        $gmailThreadId = $gmailMessage['threadId'] ?? $gmailMessageId;

        if (! $gmailMessageId || $this->messageAlreadyImported($account->business_id, $gmailMessageId)) {
            return false;
        }

        $headers = $this->headers($gmailMessage['payload']['headers'] ?? []);
        [$fromName, $fromEmail] = $this->parseAddress($headers['from'] ?? 'Unknown Sender');
        [, $toEmail] = $this->parseAddress($headers['to'] ?? ($account->provider_meta['email'] ?? $account->account_name));
        $subject = $headers['subject'] ?? '(no subject)';
        $body = $this->sanitizeEmailBody($this->bodyFromPayload($gmailMessage['payload'] ?? [])) ?: '(empty email)';
        $date = $this->messageDate($gmailMessage, $headers);
        $replyDisabled = $this->replyDisabled($headers, $fromEmail);

        $customer = Customer::firstOrCreate(
            [
                'business_id' => $account->business_id,
                'external_id' => $fromEmail,
                'channel' => 'Gmail',
            ],
            [
                'name' => $fromName ?: $fromEmail,
                'tags' => ['gmail'],
            ]
        );

        $conversation = $this->findConversationByThread($account, $gmailThreadId)
            ?: Conversation::create([
                'business_id' => $account->business_id,
                'connected_account_id' => $account->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_external_id' => $fromEmail,
                'channel' => 'Gmail',
                'status' => Conversation::STATE_NEEDS_HUMAN,
                'ai_mode' => 'human',
                'last_message_at' => $date,
            ]);

        $conversation->update([
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_external_id' => $fromEmail,
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => $date,
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $account->business_id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => $body,
            'metadata' => [
                'source' => 'gmail',
                'gmail_message_id' => $gmailMessageId,
                'gmail_thread_id' => $gmailThreadId,
                'subject' => $subject,
                'from_email' => $fromEmail,
                'to_email' => $toEmail,
                'internal_date' => $gmailMessage['internalDate'] ?? null,
                'reply_disabled' => $replyDisabled['disabled'],
                'reply_disabled_reason' => $replyDisabled['reason'],
            ],
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $this->importAttachments($account, $message, $gmailMessage);

        return true;
    }

    private function validAccessToken(ConnectedAccount $account): string
    {
        if ($account->token_expires_at && $account->token_expires_at->lte(now()->addMinute())) {
            $this->refreshAccessToken($account);
        }

        if (! $account->access_token) {
            throw new RuntimeException('Gmail account is missing an access token.');
        }

        return $account->access_token;
    }

    private function refreshAccessToken(ConnectedAccount $account): void
    {
        if (! $account->refresh_token) {
            throw new RuntimeException('Gmail account must be reconnected before syncing.');
        }

        $tokens = $this->googleHttp()->asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.gmail.client_id'),
            'client_secret' => config('services.gmail.client_secret'),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ])->throw()->json();

        $account->update([
            'access_token' => $tokens['access_token'],
            'token_expires_at' => isset($tokens['expires_in'])
                ? now()->addSeconds((int) $tokens['expires_in'])
                : $account->token_expires_at,
        ]);

        $account->refresh();
    }

    private function importAttachments(ConnectedAccount $account, Message $message, array $gmailMessage): void
    {
        $token = $this->validAccessToken($account);

        foreach ($this->attachmentParts($gmailMessage['payload'] ?? []) as $part) {
            $attachmentId = $part['body']['attachmentId'] ?? null;
            $filename = trim((string) ($part['filename'] ?? ''));
            $mimeType = $part['mimeType'] ?? 'application/octet-stream';

            if (! $attachmentId || $filename === '' || $this->shouldSkipAttachment($mimeType)) {
                continue;
            }

            $providerAttachmentId = ($gmailMessage['id'] ?? 'unknown-message').':'.$attachmentId;

            if (MessageAttachment::where('business_id', $account->business_id)
                ->where('provider', 'gmail')
                ->where('provider_attachment_id', $providerAttachmentId)
                ->exists()) {
                continue;
            }

            $attachment = $this->gmail($token)
                ->get(self::GMAIL_API_BASE.'/users/me/messages/'.$gmailMessage['id'].'/attachments/'.$attachmentId)
                ->throw()
                ->json();
            $contents = $this->decodeBase64Url((string) ($attachment['data'] ?? ''));

            if ($contents === '') {
                continue;
            }

            $safeFilename = Str::limit(Str::slug(pathinfo($filename, PATHINFO_FILENAME)), 80, '');
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $storedFilename = ($safeFilename ?: 'attachment').($extension ? '.'.$extension : '');
            $path = 'gmail-attachments/'.$account->business_id.'/'.$message->id.'/'.$attachmentId.'-'.$storedFilename;

            Storage::disk('local')->put($path, $contents);

            MessageAttachment::create([
                'message_id' => $message->id,
                'business_id' => $account->business_id,
                'provider' => 'gmail',
                'provider_attachment_id' => $providerAttachmentId,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'size' => $part['body']['size'] ?? strlen($contents),
                'disk' => 'local',
                'storage_path' => $path,
                'metadata' => [
                    'gmail_message_id' => $gmailMessage['id'] ?? null,
                    'gmail_attachment_id' => $attachmentId,
                ],
            ]);
        }
    }

    private function attachmentParts(array $part): array
    {
        $parts = [];

        if (! empty($part['filename']) && ! empty($part['body']['attachmentId'])) {
            $parts[] = $part;
        }

        foreach ($part['parts'] ?? [] as $childPart) {
            $parts = array_merge($parts, $this->attachmentParts($childPart));
        }

        return $parts;
    }

    private function shouldSkipAttachment(?string $mimeType): bool
    {
        $mimeType = strtolower((string) $mimeType);

        return str_starts_with($mimeType, 'audio/') || str_starts_with($mimeType, 'video/');
    }

    private function fetchProfile(string $accessToken): array
    {
        return $this->gmail($accessToken)
            ->get(self::GMAIL_API_BASE.'/users/me/profile')
            ->throw()
            ->json();
    }

    private function gmail(string $accessToken): PendingRequest
    {
        return $this->googleHttp()->withToken($accessToken)->acceptJson();
    }

    private function googleHttp(): PendingRequest
    {
        return Http::timeout(self::HTTP_TIMEOUT_SECONDS)
            ->connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS);
    }

    private function scopes(): array
    {
        return [
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.send',
        ];
    }

    private function messageAlreadyImported(int $businessId, string $gmailMessageId): bool
    {
        return Message::where('business_id', $businessId)
            ->where('metadata->gmail_message_id', $gmailMessageId)
            ->exists();
    }

    private function findConversationByThread(ConnectedAccount $account, string $threadId): ?Conversation
    {
        return Conversation::where('business_id', $account->business_id)
            ->where('connected_account_id', $account->id)
            ->where('channel', 'Gmail')
            ->whereHas('messages', fn ($query) => $query->where('metadata->gmail_thread_id', $threadId))
            ->latest('last_message_at')
            ->first();
    }

    private function headers(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $header) {
            if (! isset($header['name'], $header['value'])) {
                continue;
            }

            $normalized[strtolower($header['name'])] = $this->decodeHeader((string) $header['value']);
        }

        return $normalized;
    }

    private function replyDisabled(array $headers, string $fromEmail): array
    {
        $fromEmail = strtolower($fromEmail);
        $autoSubmitted = strtolower($headers['auto-submitted'] ?? '');
        $precedence = strtolower($headers['precedence'] ?? '');

        if (preg_match('/(^|[._+-])(no-?reply|do-?not-?reply|donotreply)([._+-]|@)/i', $fromEmail)) {
            return ['disabled' => true, 'reason' => 'Automated sender'];
        }

        if ($autoSubmitted !== '' && $autoSubmitted !== 'no') {
            return ['disabled' => true, 'reason' => 'Automated email'];
        }

        if (in_array($precedence, ['bulk', 'junk', 'list'], true) || isset($headers['list-unsubscribe'])) {
            return ['disabled' => true, 'reason' => 'Bulk or mailing-list email'];
        }

        return ['disabled' => false, 'reason' => null];
    }

    private function decodeHeader(string $value): string
    {
        return function_exists('mb_decode_mimeheader') ? mb_decode_mimeheader($value) : $value;
    }

    private function parseAddress(string $value): array
    {
        $value = trim($this->decodeHeader($value));

        if (preg_match('/^(?:"?([^"<]*)"?\s*)?<([^<>]+@[^<>]+)>$/', $value, $matches)) {
            return [trim($matches[1]) ?: $matches[2], strtolower(trim($matches[2]))];
        }

        if (preg_match('/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/i', $value, $matches)) {
            return [$matches[1], strtolower($matches[1])];
        }

        $fallback = Str::slug($value) ?: 'unknown-sender';

        return [$value ?: 'Unknown Sender', $fallback.'@unknown.gmail'];
    }

    private function bodyFromPayload(array $payload): string
    {
        $plain = $this->partBody($payload, 'text/plain');

        if ($plain !== '') {
            return $plain;
        }

        return $this->textFromHtml($this->partBody($payload, 'text/html'));
    }

    private function partBody(array $part, string $mimeType): string
    {
        if (($part['mimeType'] ?? null) === $mimeType && ! empty($part['body']['data'])) {
            return $this->decodeBody($part['body']['data']);
        }

        foreach ($part['parts'] ?? [] as $childPart) {
            $body = $this->partBody($childPart, $mimeType);

            if ($body !== '') {
                return $body;
            }
        }

        return '';
    }

    private function decodeBody(string $body): string
    {
        return trim($this->decodeBase64Url($body));
    }

    private function decodeBase64Url(string $body): string
    {
        $body = strtr($body, '-_', '+/');
        $body .= str_repeat('=', (4 - strlen($body) % 4) % 4);

        return (string) base64_decode($body, true);
    }

    private function sanitizeEmailBody(string $body): string
    {
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('/<(?:!doctype|html|head|body|style|table|div|span|p|br)\b/i', $body)) {
            $htmlStart = preg_match('/<!doctype|<html|<head|<body|<style/i', $body, $matches, PREG_OFFSET_CAPTURE)
                ? $matches[0][1]
                : 0;

            $beforeHtml = trim(substr($body, 0, $htmlStart));
            $htmlText = $this->textFromHtml(substr($body, $htmlStart));
            $body = $beforeHtml !== '' ? $beforeHtml : $htmlText;
        }

        return $this->normalizeEmailText($body);
    }

    private function textFromHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<\s*(script|style|head|noscript)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\s*\/\s*(p|div|tr|li|h[1-6])\s*>/i', "\n", $html) ?? $html;
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = $this->normalizeEmailText($text);

        return trim($text);
    }

    private function normalizeEmailText(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = preg_replace('/^\s*Subject:\s*[^\n]+(?:\n){1,2}/i', '', $body) ?? $body;
        $body = preg_replace('/\nOn .+?wrote:\n(?:>.*\n?)+/is', "\n", $body) ?? $body;
        $body = preg_replace('/^\s*>.*(?:\n|$)/m', '', $body) ?? $body;
        $body = preg_replace('/[ \t]+/', ' ', $body) ?? $body;
        $body = preg_replace('/[ \t]*\n[ \t]*/', "\n", $body) ?? $body;
        $body = preg_replace("/\n{3,}/", "\n\n", $body) ?? $body;

        $lines = array_map(static fn (string $line): string => trim($line), explode("\n", $body));
        $body = implode("\n", $lines);

        return trim($body);
    }

    private function messageDate(array $gmailMessage, array $headers): Carbon
    {
        if (! empty($gmailMessage['internalDate'])) {
            return Carbon::createFromTimestampMs((int) $gmailMessage['internalDate']);
        }

        if (! empty($headers['date'])) {
            return Carbon::parse($headers['date']);
        }

        return now();
    }
}
