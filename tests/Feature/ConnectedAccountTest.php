<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\User;
use App\Services\MessageIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_can_add_multiple_accounts_for_the_same_platform(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);

        $this->actingAs($user)
            ->post(route('dashboard.accounts.fake-connect'), [
                'platform' => 'WhatsApp',
                'account_name' => 'Main WhatsApp',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('dashboard.accounts.fake-connect'), [
                'platform' => 'WhatsApp',
                'account_name' => 'Support WhatsApp',
            ])
            ->assertRedirect();

        $accounts = ConnectedAccount::where('business_id', $business->id)
            ->where('platform', 'WhatsApp')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $accounts);
        $this->assertSame(['Main WhatsApp', 'Support WhatsApp'], $accounts->pluck('account_name')->all());
        $this->assertCount(2, $accounts->pluck('external_account_id')->unique());
    }

    public function test_message_ingestion_routes_same_platform_messages_by_account_identifier(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $service = app(MessageIngestionService::class);

        $firstConversation = $service->ingest([
            'business_id' => $business->id,
            'channel' => 'Instagram',
            'external_account_id' => 'ig-account-one',
            'customer_external_id' => 'customer-one',
            'customer_name' => 'Customer One',
            'body' => 'How much is detailing?',
        ]);

        $secondConversation = $service->ingest([
            'business_id' => $business->id,
            'channel' => 'Instagram',
            'external_account_id' => 'ig-account-two',
            'customer_external_id' => 'customer-two',
            'customer_name' => 'Customer Two',
            'body' => 'Can I book tomorrow?',
        ]);

        $this->assertNotSame($firstConversation->connected_account_id, $secondConversation->connected_account_id);
        $this->assertDatabaseHas('connected_accounts', [
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'external_account_id' => 'ig-account-one',
        ]);
        $this->assertDatabaseHas('connected_accounts', [
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'external_account_id' => 'ig-account-two',
        ]);
    }

    public function test_workspace_can_disconnect_account_without_deleting_it(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Lagos Instagram',
            'external_account_id' => 'lagos-instagram-main',
            'status' => 'connected',
            'connected_at' => now(),
            'access_token' => 'fake-demo-token',
        ]);

        $this->actingAs($user)
            ->patch(route('dashboard.accounts.disconnect', $account))
            ->assertRedirect();

        $account->refresh();

        $this->assertSame('disconnected', $account->status);
        $this->assertNull($account->connected_at);
        $this->assertNull($account->access_token);
        $this->assertDatabaseHas('connected_accounts', [
            'id' => $account->id,
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'status' => 'disconnected',
        ]);
    }

    public function test_accounts_page_hides_disconnected_accounts(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Active Instagram',
            'external_account_id' => 'active-instagram-main',
            'status' => 'connected',
            'connected_at' => now(),
        ]);
        ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'WhatsApp',
            'account_name' => 'Old WhatsApp',
            'external_account_id' => 'old-whatsapp-main',
            'status' => 'disconnected',
            'connected_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.accounts'));

        $response->assertOk();
        $response->assertSee('Active Instagram');
        $response->assertDontSee('Old WhatsApp');
        $response->assertDontSee('disconnected');
    }

    public function test_user_cannot_disconnect_another_workspace_account(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $this->createBusiness($user, 'Lagos Detailing');
        $foreignBusiness = $this->createBusiness($owner, 'VIP Rentals');
        $account = ConnectedAccount::create([
            'business_id' => $foreignBusiness->id,
            'platform' => 'WhatsApp',
            'account_name' => 'VIP WhatsApp',
            'external_account_id' => 'vip-whatsapp-main',
            'status' => 'connected',
            'connected_at' => now(),
            'access_token' => 'fake-demo-token',
        ]);

        $this->actingAs($user)
            ->patch(route('dashboard.accounts.disconnect', $account))
            ->assertForbidden();

        $this->assertSame('connected', $account->fresh()->status);
    }

    private function createBusiness(User $owner, string $name = 'Lagos Detailing'): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => str($name)->slug().'-test',
            'category' => 'Auto care',
            'email' => str($name)->slug().'@example.test',
        ]);

        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }
}
