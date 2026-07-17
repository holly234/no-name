<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkspaceDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_permanently_delete_last_workspace_and_user_account(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $business = $this->createBusiness($owner, 'Delete Me');
        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Gmail',
            'account_name' => 'Inbox',
            'external_account_id' => 'delete@example.test',
            'access_token' => 'encrypted-by-model',
        ]);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Customer',
            'external_id' => 'customer-1',
            'channel' => 'Gmail',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_external_id' => $customer->external_id,
            'channel' => 'Gmail',
        ]);
        $message = Message::create([
            'business_id' => $business->id,
            'conversation_id' => $conversation->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Delete this message.',
        ]);

        Storage::disk('local')->put('attachments/delete-me.txt', 'private data');
        MessageAttachment::create([
            'business_id' => $business->id,
            'message_id' => $message->id,
            'provider' => 'gmail',
            'provider_attachment_id' => 'attachment-1',
            'filename' => 'delete-me.txt',
            'disk' => 'local',
            'storage_path' => 'attachments/delete-me.txt',
        ]);

        $this->actingAs($owner)
            ->delete(route('dashboard.settings.workspace.destroy'), [
                'workspace_name' => 'Delete Me',
                'confirmation' => 'DELETE',
            ])
            ->assertRedirect(route('landing'));

        $this->assertGuest();
        $this->assertDatabaseMissing('businesses', ['id' => $business->id]);
        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('connected_accounts', ['id' => $account->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
        Storage::disk('local')->assertMissing('attachments/delete-me.txt');
    }

    public function test_non_owner_cannot_delete_workspace(): void
    {
        $owner = User::factory()->create();
        $agent = User::factory()->create();
        $business = $this->createBusiness($owner, 'Protected Workspace');
        $business->users()->attach($agent->id, ['role' => 'Agent']);

        $this->actingAs($agent)
            ->delete(route('dashboard.settings.workspace.destroy'), [
                'workspace_name' => 'Protected Workspace',
                'confirmation' => 'DELETE',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
    }

    public function test_exact_workspace_name_and_delete_confirmation_are_required(): void
    {
        $owner = User::factory()->create();
        $business = $this->createBusiness($owner, 'Exact Name');

        $this->actingAs($owner)
            ->delete(route('dashboard.settings.workspace.destroy'), [
                'workspace_name' => 'Wrong Name',
                'confirmation' => 'delete',
            ])
            ->assertSessionHasErrorsIn('workspaceDeletion');

        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    public function test_deleting_one_workspace_preserves_user_and_other_workspace(): void
    {
        $owner = User::factory()->create();
        $deletedBusiness = $this->createBusiness($owner, 'Old Workspace');
        $remainingBusiness = $this->createBusiness($owner, 'Remaining Workspace');

        $this->actingAs($owner)
            ->withSession(['current_business_id' => $deletedBusiness->id])
            ->delete(route('dashboard.settings.workspace.destroy'), [
                'workspace_name' => 'Old Workspace',
                'confirmation' => 'DELETE',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('current_business_id', $remainingBusiness->id);

        $this->assertAuthenticatedAs($owner);
        $this->assertDatabaseMissing('businesses', ['id' => $deletedBusiness->id]);
        $this->assertDatabaseHas('businesses', ['id' => $remainingBusiness->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    private function createBusiness(User $owner, string $name): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.str()->random(5),
            'webhook_secret' => 'test-secret-'.str()->random(12),
        ]);

        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }
}
