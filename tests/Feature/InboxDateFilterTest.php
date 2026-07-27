<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxDateFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_conversations_show_their_date_and_can_be_filtered_by_that_date(): void
    {
        $this->travelTo('2026-07-27 12:00:00');

        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Dated Inbox',
            'slug' => 'dated-inbox',
            'email' => 'dated-inbox@example.test',
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Gmail',
            'account_name' => 'Dated Inbox Gmail',
            'external_account_id' => 'dated-inbox-gmail',
            'status' => 'connected',
        ]);

        $oldConversation = $this->createConversation(
            $business,
            $account,
            'Old Conversation',
            'old-customer',
            now()->subDays(2)->setTime(9, 30),
        );
        $this->createConversation(
            $business,
            $account,
            'Recent Conversation',
            'recent-customer',
            now()->subHour(),
        );

        $response = $this
            ->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->get(route('dashboard.inbox', ['exact_date' => '2026-07-25']));

        $response
            ->assertOk()
            ->assertSee('Old Conversation')
            ->assertDontSee('Recent Conversation')
            ->assertSee('25 Jul')
            ->assertSee('datetime="'.$oldConversation->last_message_at->toIso8601String().'"', false)
            ->assertSee('value="2026-07-25"', false);
    }

    private function createConversation(
        Business $business,
        ConnectedAccount $account,
        string $name,
        string $externalId,
        mixed $lastMessageAt,
    ): Conversation {
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => $name,
            'external_id' => $externalId,
            'channel' => 'Gmail',
        ]);

        return Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => $name,
            'customer_external_id' => $externalId,
            'channel' => 'Gmail',
            'status' => Conversation::STATE_INFORMATIONAL,
            'ai_mode' => 'ai',
            'last_message_at' => $lastMessageAt,
        ]);
    }
}
