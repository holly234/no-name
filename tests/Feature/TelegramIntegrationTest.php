<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_can_connect_telegram_account(): void
    {
        config(['app.url' => 'https://perpetual.test']);
        Http::fake([
            'https://api.telegram.org/bot123456:test-token/setWebhook' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $user = User::factory()->create();
        $this->createBusiness($user);

        $response = $this
            ->actingAs($user)
            ->post(route('dashboard.accounts.telegram.connect'), [
                'account_name' => 'Support Telegram',
                'bot_username' => 'BrandSupportBot',
                'bot_token' => '123456:test-token',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Telegram account connected and webhook is live.');

        $account = ConnectedAccount::where('platform', 'Telegram')->firstOrFail();

        $this->assertSame('Support Telegram', $account->account_name);
        $this->assertSame('@BrandSupportBot', $account->external_account_id);
        $this->assertSame('123456:test-token', $account->access_token);
        $this->assertNotEmpty($account->provider_meta['webhook_secret']);
        $this->assertSame('active', $account->provider_meta['webhook_status']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bot123456:test-token/setWebhook'
            && $request['url'] === 'https://perpetual.test/api/webhooks/telegram/'.$account->id
            && $request['secret_token'] === $account->provider_meta['webhook_secret']);
    }

    public function test_telegram_webhook_requires_account_secret(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createTelegramAccount($business);

        $this
            ->postJson('/api/webhooks/telegram/'.$account->id, $this->telegramUpdate())
            ->assertUnauthorized();
    }

    public function test_telegram_webhook_imports_message_to_inbox(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        AiSetting::create([
            'business_id' => $business->id,
            'auto_reply_enabled' => false,
            'human_takeover_enabled' => true,
        ]);
        $account = $this->createTelegramAccount($business);

        $response = $this
            ->withHeader('X-Telegram-Bot-Api-Secret-Token', $account->provider_meta['webhook_secret'])
            ->postJson('/api/webhooks/telegram/'.$account->id, $this->telegramUpdate());

        $response->assertOk();
        $response->assertJson(['message' => 'Telegram message processed.']);

        $this->assertDatabaseHas('conversations', [
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'channel' => 'Telegram',
            'customer_name' => 'Ada Johnson',
            'customer_external_id' => '98765',
            'status' => Conversation::STATE_NEEDS_HUMAN,
        ]);
        $this->assertDatabaseHas('messages', [
            'business_id' => $business->id,
            'direction' => 'incoming',
            'body' => 'Hello from Telegram',
        ]);
    }

    public function test_telegram_webhook_uses_the_exact_connected_account_from_the_route(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Telegram',
            'account_name' => 'Old Telegram',
            'external_account_id' => '@BrandSupportBot',
            'status' => 'disconnected',
            'connected_at' => now()->subDay(),
            'access_token' => null,
            'provider_meta' => [
                'bot_username' => '@BrandSupportBot',
            ],
        ]);
        $account = $this->createTelegramAccount($business);

        $this
            ->withHeader('X-Telegram-Bot-Api-Secret-Token', $account->provider_meta['webhook_secret'])
            ->postJson('/api/webhooks/telegram/'.$account->id, $this->telegramUpdate())
            ->assertOk();

        $this->assertDatabaseHas('conversations', [
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_external_id' => '98765',
            'channel' => 'Telegram',
        ]);
    }

    public function test_telegram_webhook_does_not_generate_placeholder_ai_reply(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        AiSetting::create([
            'business_id' => $business->id,
            'auto_reply_enabled' => true,
            'human_takeover_enabled' => true,
        ]);
        $account = $this->createTelegramAccount($business);

        $this
            ->withHeader('X-Telegram-Bot-Api-Secret-Token', $account->provider_meta['webhook_secret'])
            ->postJson('/api/webhooks/telegram/'.$account->id, $this->telegramUpdate())
            ->assertOk();

        $conversation = Conversation::where('business_id', $business->id)
            ->where('channel', 'Telegram')
            ->where('customer_external_id', '98765')
            ->firstOrFail();

        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->status);
        $this->assertSame('human', $conversation->ai_mode);
        $this->assertSame(1, $conversation->messages()->count());
        $this->assertFalse($conversation->messages()->where('sender_type', 'ai')->exists());
    }

    public function test_staff_reply_to_telegram_sends_through_bot_api(): void
    {
        Http::fake([
            'https://api.telegram.org/bot123456:test-token/sendMessage' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 77,
                    'chat' => ['id' => 98765],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createTelegramAccount($business);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Ada Johnson',
            'external_id' => '98765',
            'channel' => 'Telegram',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => 'Ada Johnson',
            'customer_external_id' => '98765',
            'channel' => 'Telegram',
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Need help',
        ]);

        $this
            ->actingAs($user)
            ->post(route('dashboard.inbox.reply', $conversation), ['body' => 'We can help.'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Reply sent.');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bot123456:test-token/sendMessage'
            && $request['chat_id'] === '98765'
            && $request['text'] === 'We can help.');

        $this->assertDatabaseHas('automation_logs', [
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'event_type' => 'manual_reply_saved',
            'status' => 'success',
            'message' => 'A staff reply was saved and the conversation is waiting for the customer.',
        ]);
    }

    public function test_staff_reply_surfaces_telegram_rejection_reason(): void
    {
        Http::fake([
            'https://api.telegram.org/bot123456:test-token/sendMessage' => Http::response([
                'ok' => false,
                'description' => 'Forbidden: bot was blocked by the user',
            ], 403),
        ]);

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createTelegramAccount($business);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Ada Johnson',
            'external_id' => '98765',
            'channel' => 'Telegram',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => 'Ada Johnson',
            'customer_external_id' => '98765',
            'channel' => 'Telegram',
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Need help',
        ]);

        $this
            ->actingAs($user)
            ->post(route('dashboard.inbox.reply', $conversation), ['body' => 'We can help.'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Reply saved locally, but Telegram rejected it: Forbidden: bot was blocked by the user');

        $this->assertDatabaseHas('automation_logs', [
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'event_type' => 'manual_reply_failed',
            'status' => 'failed',
            'message' => 'Telegram rejected the reply.',
        ]);
    }

    public function test_staff_reply_requires_telegram_ok_response(): void
    {
        Http::fake([
            'https://api.telegram.org/bot123456:test-token/sendMessage' => Http::response([
                'ok' => false,
                'description' => 'Bad Request: chat not found',
            ], 200),
        ]);

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createTelegramAccount($business);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Ada Johnson',
            'external_id' => '98765',
            'channel' => 'Telegram',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => 'Ada Johnson',
            'customer_external_id' => '98765',
            'channel' => 'Telegram',
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Need help',
        ]);

        $this
            ->actingAs($user)
            ->post(route('dashboard.inbox.reply', $conversation), ['body' => 'We can help.'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Reply saved locally, but Telegram did not confirm delivery: Bad Request: chat not found');

        $this->assertDatabaseHas('automation_logs', [
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'event_type' => 'manual_reply_failed',
            'status' => 'failed',
            'message' => 'Telegram reply failed unexpectedly.',
        ]);
    }

    public function test_staff_reply_fails_when_conversation_is_linked_to_disconnected_telegram_account(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Telegram',
            'account_name' => 'Old Telegram',
            'external_account_id' => '@BrandSupportBot',
            'status' => 'disconnected',
            'connected_at' => now()->subDay(),
            'access_token' => null,
            'provider_meta' => [
                'bot_username' => '@BrandSupportBot',
            ],
        ]);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Ada Johnson',
            'external_id' => '98765',
            'channel' => 'Telegram',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => 'Ada Johnson',
            'customer_external_id' => '98765',
            'channel' => 'Telegram',
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Need help',
        ]);

        $this
            ->actingAs($user)
            ->post(route('dashboard.inbox.reply', $conversation), ['body' => 'We can help.'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Reply saved locally, but Telegram did not confirm delivery: The Telegram account for this conversation is disconnected.');
    }

    private function createBusiness(User $owner): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Lagos Detailing',
            'slug' => 'lagos-detailing-telegram-test',
            'category' => 'Auto care',
            'email' => 'lagos-telegram@example.test',
        ]);

        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }

    private function createTelegramAccount(Business $business): ConnectedAccount
    {
        return ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Telegram',
            'account_name' => 'Support Telegram',
            'external_account_id' => '@BrandSupportBot',
            'status' => 'connected',
            'connected_at' => now(),
            'access_token' => '123456:test-token',
            'provider_meta' => [
                'webhook_secret' => 'telegram-secret',
                'bot_username' => '@BrandSupportBot',
            ],
        ]);
    }

    private function telegramUpdate(): array
    {
        return [
            'update_id' => 1000,
            'message' => [
                'message_id' => 42,
                'chat' => [
                    'id' => 98765,
                    'type' => 'private',
                    'username' => 'adajohnson',
                ],
                'from' => [
                    'id' => 98765,
                    'is_bot' => false,
                    'first_name' => 'Ada',
                    'last_name' => 'Johnson',
                    'username' => 'adajohnson',
                ],
                'text' => 'Hello from Telegram',
            ],
        ];
    }
}
