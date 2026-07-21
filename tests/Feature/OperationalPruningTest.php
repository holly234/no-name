<?php

namespace Tests\Feature;

use App\Models\AutomationLog;
use App\Models\Business;
use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalPruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_pruning_removes_old_operational_noise_but_keeps_recent_records(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Prune Test',
            'slug' => 'prune-test',
            'email' => $owner->email,
        ]);
        $oldLog = AutomationLog::create([
            'business_id' => $business->id,
            'event_type' => 'test',
            'status' => 'success',
            'message' => 'Old routine log',
        ]);
        $oldLog->forceFill(['created_at' => now()->subDays(31)])->save();
        $recentLog = AutomationLog::create([
            'business_id' => $business->id,
            'event_type' => 'test',
            'status' => 'success',
            'message' => 'Recent routine log',
        ]);
        $invite = TeamInvite::create([
            'business_id' => $business->id,
            'email' => 'old@example.com',
            'role' => 'agent',
            'token' => str()->random(64),
            'status' => 'cancelled',
            'invited_by' => $owner->id,
            'expires_at' => now()->subDays(40),
        ]);
        $invite->forceFill(['updated_at' => now()->subDays(31)])->save();

        $this->artisan('ops:prune')->assertSuccessful();

        $this->assertDatabaseMissing('automation_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('automation_logs', ['id' => $recentLog->id]);
        $this->assertDatabaseMissing('team_invites', ['id' => $invite->id]);
    }
}
