<?php

namespace Tests\Feature;

use App\Models\AiCreditTransaction;
use App\Models\AiCreditWallet;
use App\Models\AiSetting;
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

    public function test_ai_assistant_page_presents_one_simple_customer_setup_area(): void
    {
        [$user, $business] = $this->workspace('Primary');

        $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->get(route('dashboard.ai-settings'))
            ->assertOk()
            ->assertSee('Ready without the homework')
            ->assertSee('Teach it about your business')
            ->assertSee('Customize it (optional)')
            ->assertSee('Team saved replies')
            ->assertDontSee('Confidence threshold')
            ->assertDontSee('Human takeover enabled');
    }

    public function test_ai_assistant_update_uses_one_canonical_handover_field(): void
    {
        [$user, $business] = $this->workspace('Primary');

        $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->patch(route('dashboard.ai-settings.update'), [
                'assistant_name' => 'Ada',
                'tone' => 'friendly',
                'auto_reply_enabled' => '1',
                'escalation_instructions' => 'Ask staff before confirming same-day delivery.',
                'fallback_response' => 'A teammate will reply shortly.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'AI settings saved.');

        $settings = AiSetting::where('business_id', $business->id)->firstOrFail();
        $this->assertSame('Ada', $settings->assistant_name);
        $this->assertSame('friendly', $settings->tone);
        $this->assertTrue($settings->auto_reply_enabled);
        $this->assertTrue($settings->human_takeover_enabled);
        $this->assertSame('Ask staff before confirming same-day delivery.', $settings->escalation_instructions);
        $this->assertNull($settings->never_say);
        $this->assertNull($settings->handover_rules);
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
