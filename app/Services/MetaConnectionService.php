<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;

class MetaConnectionService
{
    public function connectDevelopmentAccount(
        int $businessId,
        string $platform,
        string $accessToken,
        string $assetId,
        ?string $businessAccountId = null,
        ?string $accountName = null,
        bool $subscribe = true,
    ): ConnectedAccount {
        $profileFields = match ($platform) {
            'WhatsApp' => 'display_phone_number,verified_name',
            'Instagram' => 'id,username,name',
            default => 'id,name',
        };
        $apiHost = $platform === 'Instagram' ? 'graph.instagram.com' : 'graph.facebook.com';
        $profile = $this->request($accessToken, $assetId.'?fields='.$profileFields, 'get', [], $apiHost);

        if ($subscribe) {
            $subscriptionAssetId = $platform === 'WhatsApp' ? $businessAccountId : $assetId;
            if (! $subscriptionAssetId) {
                throw new \RuntimeException('A WhatsApp Business Account ID is required to subscribe this test account.');
            }

            $payload = match ($platform) {
                'WhatsApp' => [],
                'Instagram' => ['subscribed_fields' => 'messages,messaging_postbacks'],
                default => ['subscribed_fields' => 'messages,messaging_postbacks,messaging_optins,message_deliveries,message_reads'],
            };
            $this->request($accessToken, $subscriptionAssetId.'/subscribed_apps', 'post', $payload, $apiHost);
        }

        $resolvedName = $accountName ?: match ($platform) {
            'WhatsApp' => $profile['verified_name'] ?? $profile['display_phone_number'] ?? 'WhatsApp Test Number',
            'Instagram' => isset($profile['username']) ? '@'.$profile['username'] : ($profile['name'] ?? 'Instagram Test Account'),
            default => $profile['name'] ?? 'Facebook Test Page',
        };

        return ConnectedAccount::updateOrCreate(
            [
                'business_id' => $businessId,
                'platform' => $platform,
                'external_account_id' => $assetId,
            ],
            [
                'account_name' => $resolvedName,
                'page_id' => in_array($platform, ['Facebook', 'Instagram'], true) ? $assetId : null,
                'phone_number_id' => $platform === 'WhatsApp' ? $assetId : null,
                'access_token' => $accessToken,
                'status' => 'connected',
                'connected_at' => now(),
                'provider_meta' => [
                    'provider' => 'meta_development',
                    'connection_mode' => 'development_manual',
                    'business_account_id' => $businessAccountId,
                    'profile' => collect($profile)->except(['access_token'])->all(),
                    'subscribed_at' => $subscribe ? now()->toIso8601String() : null,
                    'webhook_url' => $this->webhookUrl(),
                ],
            ],
        );
    }

    public function connectEmbeddedSignup(
        int $businessId,
        string $code,
        string $businessAccountId,
        string $phoneNumberId
    ): ConnectedAccount {
        $tokenResponse = Http::timeout(15)->acceptJson()->get(
            'https://graph.facebook.com/'.config('services.meta.graph_version', 'v23.0').'/oauth/access_token',
            [
                'client_id' => config('services.meta.app_id'),
                'client_secret' => config('services.meta.app_secret'),
                'code' => $code,
            ],
        );

        if (! $tokenResponse->successful() || ! $tokenResponse->json('access_token')) {
            throw new \RuntimeException($tokenResponse->json('error.message') ?: 'Meta signup code could not be exchanged.');
        }

        $accessToken = (string) $tokenResponse->json('access_token');
        $profile = $this->request($accessToken, $phoneNumberId.'?fields=display_phone_number,verified_name', 'get');
        $subscribe = $this->request($accessToken, $businessAccountId.'/subscribed_apps', 'post', []);

        if (($subscribe['success'] ?? true) !== true) {
            throw new \RuntimeException('Meta could not subscribe this WhatsApp Business account to the app.');
        }

        return ConnectedAccount::updateOrCreate(
            [
                'business_id' => $businessId,
                'platform' => 'WhatsApp',
                'external_account_id' => $phoneNumberId,
            ],
            [
                'account_name' => $profile['verified_name'] ?? ($profile['display_phone_number'] ?? 'WhatsApp Business'),
                'phone_number_id' => $phoneNumberId,
                'access_token' => $accessToken,
                'status' => 'connected',
                'connected_at' => now(),
                'provider_meta' => [
                    'provider' => 'meta_whatsapp_embedded_signup',
                    'business_account_id' => $businessAccountId,
                    'display_phone_number' => $profile['display_phone_number'] ?? null,
                    'verified_name' => $profile['verified_name'] ?? null,
                    'embedded_signup' => true,
                    'subscribed_at' => now()->toIso8601String(),
                    'webhook_url' => $this->webhookUrl(),
                ],
            ]
        );
    }

    public function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/api/webhooks/meta';
    }

    public function sendWhatsAppText(Conversation $conversation, string $body): array
    {
        $account = $conversation->connectedAccount;

        if (! $account || $account->platform !== 'WhatsApp' || ! $account->access_token) {
            throw new \RuntimeException('This conversation is not linked to a connected WhatsApp account.');
        }

        return $this->request($account->access_token, $account->phone_number_id, 'post', [
            'messaging_product' => 'whatsapp',
            'to' => $conversation->customer_external_id,
            'type' => 'text',
            'text' => ['preview_url' => true, 'body' => $body],
        ]);
    }

    public function sendMessengerText(Conversation $conversation, string $body): array
    {
        $account = $conversation->connectedAccount;
        if (! $account || ! in_array($account->platform, ['Facebook', 'Instagram'], true) || ! $account->access_token) {
            throw new \RuntimeException('This conversation is not linked to a connected Meta messaging account.');
        }

        $assetId = $account->page_id ?: $account->external_account_id;

        $payload = [
            'recipient' => ['id' => $conversation->customer_external_id],
            'message' => ['text' => $body],
        ];
        if ($account->platform === 'Facebook') {
            $payload['messaging_type'] = 'RESPONSE';
        }

        return $this->request(
            $account->access_token,
            $assetId.'/messages',
            'post',
            $payload,
            $account->platform === 'Instagram' ? 'graph.instagram.com' : 'graph.facebook.com',
        );
    }

    private function request(string $accessToken, string $path, string $method, array $payload = [], string $host = 'graph.facebook.com'): array
    {
        $url = 'https://'.$host.'/'.config('services.meta.graph_version', 'v25.0').'/'.$path;
        $request = Http::timeout(15)->withToken($accessToken)->acceptJson();
        $response = $method === 'get' ? $request->get($url) : $request->post($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException($response->json('error.message') ?: 'Meta rejected the request.');
        }

        return $response->json() ?? [];
    }
}
