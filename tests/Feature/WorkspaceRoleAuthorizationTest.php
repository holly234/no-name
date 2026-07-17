<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_use_inbox_but_cannot_access_operational_or_owner_pages(): void
    {
        [$owner, $business] = $this->workspace();
        $agent = User::factory()->create();
        $business->users()->attach($agent->id, ['role' => 'Agent']);

        $this->asWorkspaceUser($agent, $business)->get(route('dashboard.inbox'))->assertOk();
        $this->asWorkspaceUser($agent, $business)->get(route('dashboard.accounts'))->assertForbidden();
        $this->asWorkspaceUser($agent, $business)->get(route('dashboard.ai-settings'))->assertForbidden();
        $this->asWorkspaceUser($agent, $business)->get(route('dashboard.knowledge-base'))->assertForbidden();
        $this->asWorkspaceUser($agent, $business)->get(route('dashboard.analytics'))->assertForbidden();
        $this->asWorkspaceUser($agent, $business)->get(route('dashboard.team'))->assertForbidden();
        $this->asWorkspaceUser($agent, $business)->get(route('dashboard.ai-credits'))->assertForbidden();
        $this->asWorkspaceUser($agent, $business)->get(route('dashboard.settings'))->assertForbidden();
    }

    public function test_admin_can_manage_operations_and_agents_but_not_owner_pages_or_admins(): void
    {
        [$owner, $business] = $this->workspace();
        $admin = User::factory()->create();
        $agent = User::factory()->create();
        $otherAdmin = User::factory()->create();
        $business->users()->attach($admin->id, ['role' => 'Admin']);
        $business->users()->attach($agent->id, ['role' => 'Agent']);
        $business->users()->attach($otherAdmin->id, ['role' => 'Admin']);

        $this->asWorkspaceUser($admin, $business)->get(route('dashboard.accounts'))->assertOk();
        $this->asWorkspaceUser($admin, $business)->get(route('dashboard.team'))->assertOk();
        $this->asWorkspaceUser($admin, $business)->get(route('dashboard.ai-credits'))->assertForbidden();
        $this->asWorkspaceUser($admin, $business)->get(route('dashboard.settings'))->assertForbidden();

        $this->asWorkspaceUser($admin, $business)
            ->patch(route('dashboard.team.members.role', $agent), ['role' => 'agent'])
            ->assertRedirect();
        $this->asWorkspaceUser($admin, $business)
            ->delete(route('dashboard.team.members.remove', $otherAdmin))
            ->assertForbidden();
        $this->asWorkspaceUser($admin, $business)
            ->post(route('dashboard.team.invite'), ['email' => 'new@example.com', 'role' => 'admin'])
            ->assertSessionHasErrors('role');
    }

    public function test_owner_can_invite_a_member_who_accepts_with_the_matching_google_email(): void
    {
        [$owner, $business] = $this->workspace();
        $invitee = User::factory()->create(['email' => 'agent@example.com']);

        $this->asWorkspaceUser($owner, $business)
            ->post(route('dashboard.team.invite'), ['email' => 'AGENT@example.com', 'role' => 'agent'])
            ->assertRedirect();

        $invite = TeamInvite::firstOrFail();
        $this->actingAs($invitee)
            ->get(route('team.invitations.accept', $invite->token))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('business_user', [
            'business_id' => $business->id,
            'user_id' => $invitee->id,
            'role' => 'Agent',
        ]);
        $this->assertSame('accepted', $invite->fresh()->status);
    }

    public function test_invitation_cannot_be_accepted_by_a_different_google_email(): void
    {
        [$owner, $business] = $this->workspace();
        $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
        $invite = TeamInvite::create([
            'business_id' => $business->id,
            'email' => 'right@example.com',
            'role' => 'agent',
            'token' => str()->random(64),
            'status' => 'pending',
            'invited_by' => $owner->id,
        ]);

        $this->actingAs($wrongUser)
            ->get(route('team.invitations.accept', $invite->token))
            ->assertForbidden();

        $this->assertDatabaseMissing('business_user', [
            'business_id' => $business->id,
            'user_id' => $wrongUser->id,
        ]);
    }

    public function test_workspace_owner_cannot_be_demoted_or_removed(): void
    {
        [$owner, $business] = $this->workspace();

        $this->asWorkspaceUser($owner, $business)
            ->patch(route('dashboard.team.members.role', $owner), ['role' => 'agent'])
            ->assertStatus(422);
        $this->asWorkspaceUser($owner, $business)
            ->delete(route('dashboard.team.members.remove', $owner))
            ->assertStatus(422);

        $this->assertTrue($business->users()->whereKey($owner->id)->exists());
    }

    private function workspace(): array
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Role Test Workspace',
            'slug' => 'role-test-'.str()->random(6),
            'email' => $owner->email,
        ]);
        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return [$owner, $business];
    }

    private function asWorkspaceUser(User $user, Business $business): static
    {
        return $this->actingAs($user)->withSession(['current_business_id' => $business->id]);
    }
}
