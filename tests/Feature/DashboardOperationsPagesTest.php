<?php

namespace Tests\Feature;

use App\Models\AiCreditTransaction;
use App\Models\AiCreditWallet;
use App\Models\AiUsageRecord;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOperationsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_credits_page_only_displays_the_current_workspace_usage(): void
    {
        [$user, $business] = $this->workspace('Primary');
        [, $otherBusiness] = $this->workspace('Other');

        AiCreditWallet::create(['business_id' => $business->id, 'balance' => 2500, 'lifetime_used' => 300]);
        AiCreditTransaction::create(['business_id' => $business->id, 'type' => 'purchase', 'credits' => 2500, 'balance_after' => 2500, 'description' => 'Starter credits']);
        AiUsageRecord::create(['business_id' => $business->id, 'provider' => 'openai', 'model' => 'gpt-test', 'input_tokens' => 100, 'output_tokens' => 40, 'credits_used' => 14]);
        AiUsageRecord::create(['business_id' => $otherBusiness->id, 'provider' => 'other-provider', 'model' => 'private-model', 'credits_used' => 999]);

        $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->get(route('dashboard.ai-credits'))
            ->assertOk()
            ->assertSee('2,500')
            ->assertSee('gpt-test')
            ->assertDontSee('private-model');
    }

    public function test_analytics_and_team_pages_are_available_to_workspace_members(): void
    {
        [$user, $business] = $this->workspace('Primary');

        $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->get(route('dashboard.analytics'))
            ->assertOk()
            ->assertSee('Workspace performance');

        $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->get(route('dashboard.team'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Owner');
    }

    private function workspace(string $name): array
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.str()->random(6),
            'email' => $user->email,
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        return [$user, $business];
    }
}
