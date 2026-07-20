<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_reply_to_foreign_business_conversation(): void
    {
        [$user, $foreignConversation] = $this->createUserAndForeignConversation();

        $response = $this
            ->actingAs($user)
            ->post(route('dashboard.inbox.reply', $foreignConversation), [
                'body' => 'This should not be saved.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $foreignConversation->id,
            'body' => 'This should not be saved.',
        ]);
    }

    public function test_user_cannot_take_over_foreign_business_conversation(): void
    {
        [$user, $foreignConversation] = $this->createUserAndForeignConversation();

        $response = $this
            ->actingAs($user)
            ->post(route('dashboard.inbox.take-over', $foreignConversation));

        $response->assertForbidden();
        $this->assertSame(Conversation::STATE_AI_HANDLING, $foreignConversation->fresh()->status);
    }

    public function test_user_cannot_resume_foreign_business_conversation(): void
    {
        [$user, $foreignConversation] = $this->createUserAndForeignConversation(Conversation::STATE_NEEDS_HUMAN);

        $response = $this
            ->actingAs($user)
            ->post(route('dashboard.inbox.resume-ai', $foreignConversation));

        $response->assertForbidden();
        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $foreignConversation->fresh()->status);
    }

    public function test_owner_can_delete_own_conversation_and_its_messages(): void
    {
        $owner = User::factory()->create();
        $business = $this->createBusiness($owner, 'Delete Test', 'delete-test');
        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Telegram',
            'account_name' => 'Delete Test Bot',
            'external_account_id' => 'delete-test-bot',
            'status' => 'connected',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_name' => 'Delete Me',
            'customer_external_id' => 'delete-me',
            'channel' => 'Telegram',
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Delete this chat.',
        ]);

        $this->actingAs($owner)
            ->withSession(['current_business_id' => $business->id])
            ->delete(route('dashboard.inbox.destroy', $conversation))
            ->assertRedirect(route('dashboard.inbox'));

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_user_cannot_delete_foreign_business_conversation(): void
    {
        [$user, $foreignConversation] = $this->createUserAndForeignConversation();

        $this->actingAs($user)
            ->delete(route('dashboard.inbox.destroy', $foreignConversation))
            ->assertNotFound();

        $this->assertDatabaseHas('conversations', ['id' => $foreignConversation->id]);
    }

    private function createUserAndForeignConversation(string $foreignStatus = Conversation::STATE_AI_HANDLING): array
    {
        $user = User::factory()->create();
        $this->createBusiness($user, 'Lagos Detailing', 'lagos-detailing-auth-test');

        $foreignOwner = User::factory()->create();
        $foreignBusiness = $this->createBusiness($foreignOwner, 'VIP Rentals', 'vip-rentals-auth-test');

        $account = ConnectedAccount::create([
            'business_id' => $foreignBusiness->id,
            'platform' => 'Instagram',
            'account_name' => 'VIP Rentals Instagram',
            'external_account_id' => 'vip-rentals-instagram-auth',
            'status' => 'connected',
        ]);

        $customer = Customer::create([
            'business_id' => $foreignBusiness->id,
            'name' => 'Ada Johnson',
            'external_id' => 'ada-instagram',
            'channel' => 'Instagram',
        ]);

        $conversation = Conversation::create([
            'business_id' => $foreignBusiness->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => 'Ada Johnson',
            'customer_external_id' => 'ada-instagram',
            'channel' => 'Instagram',
            'status' => $foreignStatus,
            'ai_mode' => $foreignStatus === Conversation::STATE_NEEDS_HUMAN ? 'human' : 'auto',
            'last_message_at' => now(),
        ]);

        return [$user, $conversation];
    }

    private function createBusiness(User $owner, string $name, string $slug): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => $slug,
            'category' => 'Demo',
            'email' => $slug.'@example.test',
        ]);

        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }
}
