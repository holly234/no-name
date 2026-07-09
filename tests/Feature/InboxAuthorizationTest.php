<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\User;
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
