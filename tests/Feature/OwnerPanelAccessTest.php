<?php

namespace Tests\Feature;

use App\Models\AutomationLog;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_the_private_owner_login(): void
    {
        $this->get('/owner')
            ->assertRedirect('/owner/login');
    }

    public function test_normal_user_cannot_access_the_platform_owner_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/owner')
            ->assertForbidden();
    }

    public function test_platform_owner_can_access_dashboard_and_resources(): void
    {
        $owner = User::factory()->create();
        $owner->forceFill(['is_platform_owner' => true])->save();

        $this->actingAs($owner)->get('/owner')->assertOk();
        $this->actingAs($owner)->get('/owner/users')->assertOk();
        $this->actingAs($owner)->get('/owner/businesses')->assertOk();
        $this->actingAs($owner)->get('/owner/connected-accounts')->assertOk();
        $this->actingAs($owner)->get('/owner/conversations')->assertOk();
        $this->actingAs($owner)->get('/owner/customers')->assertOk();
        $this->actingAs($owner)->get('/owner/ai-settings')->assertOk();
        $this->actingAs($owner)->get('/owner/automation-logs')->assertOk();
        $this->actingAs($owner)->get('/owner/revenue')->assertOk();
        $this->actingAs($owner)->get('/owner/system-health')->assertOk();
    }

    public function test_suspended_workspace_cannot_open_customer_dashboard(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Suspended Workspace',
            'slug' => 'suspended-workspace',
            'is_suspended' => true,
            'suspended_at' => now(),
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->get('/dashboard/inbox')
            ->assertForbidden();
    }

    public function test_platform_owner_can_open_an_automation_log_record(): void
    {
        $owner = User::factory()->create(['is_platform_owner' => true]);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Owner Test Workspace',
            'slug' => 'owner-test-workspace',
        ]);
        $log = AutomationLog::create([
            'business_id' => $business->id,
            'event_type' => 'webhook.received',
            'status' => 'success',
            'message' => 'Webhook processed.',
            'metadata' => ['provider' => 'telegram'],
        ]);

        $this->actingAs($owner)
            ->get("/owner/automation-logs/{$log->id}")
            ->assertOk()
            ->assertSee('telegram');
    }
}
