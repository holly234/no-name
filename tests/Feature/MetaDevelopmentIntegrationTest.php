<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\User;
use App\Services\MetaConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaDevelopmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_connector_is_hidden_when_disabled(): void
    {
        config(['services.meta.development_connect_enabled' => false]);
        [$user] = $this->workspace();

        $this->actingAs($user)
            ->post(route('dashboard.accounts.meta.development-connect'), [
                'platform' => 'WhatsApp',
                'asset_id' => 'phone-1',
                'business_account_id' => 'waba-1',
                'access_token' => 'secret-token',
            ])
            ->assertNotFound();
    }

    public function test_workspace_can_validate_and_store_encrypted_whatsapp_test_connection(): void
    {
        config(['services.meta.development_connect_enabled' => true]);
        Http::fake([
            'graph.facebook.com/*/phone-1?fields=*' => Http::response([
                'id' => 'phone-1',
                'display_phone_number' => '+15550001111',
                'verified_name' => 'Test Store',
            ]),
            'graph.facebook.com/*/waba-1/subscribed_apps' => Http::response(['success' => true]),
        ]);
        [$user, $business] = $this->workspace();

        $this->actingAs($user)
            ->post(route('dashboard.accounts.meta.development-connect'), [
                'platform' => 'WhatsApp',
                'asset_id' => 'phone-1',
                'business_account_id' => 'waba-1',
                'access_token' => 'secret-token',
                'subscribe_webhooks' => '1',
            ])
            ->assertRedirect();

        $account = ConnectedAccount::where('business_id', $business->id)->where('platform', 'WhatsApp')->firstOrFail();
        $this->assertSame('secret-token', $account->access_token);
        $this->assertSame('development_manual', $account->provider_meta['connection_mode']);
        $this->assertNotSame('secret-token', DB::table('connected_accounts')->where('id', $account->id)->value('access_token'));
    }

    public function test_signed_facebook_webhook_routes_message_to_matching_workspace_page(): void
    {
        config(['services.meta.app_secret' => 'meta-secret']);
        [, $business] = $this->workspace();
        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Facebook',
            'account_name' => 'Test Page',
            'external_account_id' => 'page-123',
            'page_id' => 'page-123',
            'access_token' => 'page-token',
            'status' => 'connected',
        ]);
        $payload = [
            'object' => 'page',
            'entry' => [[
                'id' => 'page-123',
                'messaging' => [[
                    'sender' => ['id' => 'psid-456'],
                    'recipient' => ['id' => 'page-123'],
                    'timestamp' => 123456789,
                    'message' => ['mid' => 'mid-1', 'text' => 'Hello from Messenger'],
                ]],
            ]],
        ];
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $json, 'meta-secret');

        $this->call('POST', '/api/webhooks/meta', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $json)->assertOk();

        $this->assertDatabaseHas('conversations', [
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'channel' => 'Facebook',
            'customer_external_id' => 'psid-456',
        ]);
        $this->assertDatabaseHas('messages', ['body' => 'Hello from Messenger', 'direction' => 'incoming']);
    }

    public function test_instagram_reply_uses_instagram_graph_host(): void
    {
        Http::fake(['graph.instagram.com/*' => Http::response(['recipient_id' => 'igsid-2', 'message_id' => 'ig-mid-2'])]);
        [, $business] = $this->workspace();
        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => '@teststore',
            'external_account_id' => 'ig-1',
            'page_id' => 'ig-1',
            'access_token' => 'ig-token',
            'status' => 'connected',
        ]);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Instagram customer',
            'external_id' => 'igsid-2',
            'channel' => 'Instagram',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_external_id' => 'igsid-2',
            'channel' => 'Instagram',
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);

        app(MetaConnectionService::class)->sendMessengerText($conversation->load('connectedAccount'), 'Hello back');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.instagram.com/v25.0/ig-1/messages')
            && $request['recipient']['id'] === 'igsid-2'
            && $request['message']['text'] === 'Hello back');
    }

    private function workspace(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Test Workspace',
            'slug' => 'test-workspace-meta',
            'category' => 'Services',
            'email' => 'owner@example.test',
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        return [$user, $business];
    }
}
