<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_message_webhook_requires_secret(): void
    {
        config(['services.webhooks.secret' => 'test-webhook-secret']);
        $business = $this->createBusiness();

        $response = $this->postJson('/api/incoming-message', [
            'business_id' => $business->id,
            'channel' => 'Instagram',
            'customer_name' => 'Kemi Adebayo',
            'customer_external_id' => 'kemi-instagram',
            'body' => 'Can I book for tomorrow?',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_incoming_message_webhook_accepts_valid_secret(): void
    {
        config(['services.webhooks.secret' => 'test-webhook-secret']);
        $business = $this->createBusiness();

        $response = $this
            ->withHeader('X-WEBHOOK-SECRET', 'test-webhook-secret')
            ->postJson('/api/incoming-message', [
                'business_id' => $business->id,
                'channel' => 'Instagram',
                'customer_name' => 'Kemi Adebayo',
                'customer_external_id' => 'kemi-instagram',
                'body' => 'Can I book for tomorrow?',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('messages', [
            'business_id' => $business->id,
            'direction' => 'incoming',
            'body' => 'Can I book for tomorrow?',
        ]);
    }

    public function test_save_outgoing_message_webhook_requires_secret(): void
    {
        config(['services.webhooks.secret' => 'test-webhook-secret']);
        $conversation = $this->createConversation();

        $response = $this->postJson('/api/save-outgoing-message', [
            'conversation_id' => $conversation->id,
            'body' => 'Thanks, we will follow up.',
            'sender_type' => 'system',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'direction' => 'outgoing',
            'body' => 'Thanks, we will follow up.',
        ]);
    }

    private function createBusiness(): Business
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Lagos Detailing',
            'slug' => 'lagos-detailing-webhook-test',
            'category' => 'Auto care',
            'email' => 'lagos-webhook@example.test',
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        return $business;
    }

    private function createConversation(): Conversation
    {
        $business = $this->createBusiness();

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Lagos Detailing Instagram',
            'external_account_id' => 'lagos-detailing-instagram-webhook',
            'status' => 'connected',
        ]);

        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Kemi Adebayo',
            'external_id' => 'kemi-instagram',
            'channel' => 'Instagram',
        ]);

        return Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => 'Kemi Adebayo',
            'customer_external_id' => 'kemi-instagram',
            'channel' => 'Instagram',
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);
    }
}
