<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_merges_demo_user_into_google_user_without_losing_workspace_connections_or_reads(): void
    {
        $source = User::factory()->create(['email' => 'demo@perpetualinbox.test']);
        $target = User::factory()->create(['email' => 'olamidetitus2@gmail.com', 'google_id' => 'google-123']);

        $business = Business::create([
            'owner_id' => $source->id,
            'name' => 'Demo Workspace',
            'slug' => 'demo-workspace',
            'webhook_secret' => 'merge-secret',
        ]);
        $business->users()->attach($source->id, ['role' => 'Owner']);

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Telegram',
            'account_name' => 'Demo Bot',
            'external_account_id' => 'telegram-1',
            'access_token' => 'secret-token',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_name' => 'Customer',
            'customer_external_id' => 'customer-1',
            'channel' => 'Telegram',
        ]);
        ConversationRead::create([
            'conversation_id' => $conversation->id,
            'user_id' => $source->id,
            'last_read_at' => now(),
        ]);

        $this->artisan('users:merge', [
            'source-email' => 'demo@perpetualinbox.test',
            'target-email' => 'olamidetitus2@gmail.com',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $source->id]);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'google_id' => 'google-123']);
        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'owner_id' => $target->id]);
        $this->assertDatabaseHas('business_user', ['business_id' => $business->id, 'user_id' => $target->id, 'role' => 'Owner']);
        $this->assertDatabaseHas('connected_accounts', ['id' => $account->id, 'business_id' => $business->id]);
        $this->assertDatabaseHas('conversation_reads', ['conversation_id' => $conversation->id, 'user_id' => $target->id]);
    }
}
