<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_shows_business_profile_form_without_api_or_billing_placeholders(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $this->createBusiness($user);

        $response = $this->actingAs($user)->get(route('dashboard.settings'));

        $response->assertOk();
        $response->assertSee('Business profile');
        $response->assertSee('Save changes');
        $response->assertDontSee('API and billing placeholders');
        $response->assertDontSee('/api/n8n/incoming-message');
        $response->assertDontSee('X-N8N-SECRET');
    }

    public function test_user_can_update_current_business_profile(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);

        $this->actingAs($user)
            ->patch(route('dashboard.settings.business.update'), [
                'name' => 'Lagos Auto Studio',
                'category' => 'Auto detailing',
                'email' => 'hello@lagosauto.test',
                'phone' => '+234 800 000 1111',
                'website' => 'https://lagosauto.test',
                'description' => 'Premium mobile and studio detailing.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Workspace profile updated.');

        $business->refresh();

        $this->assertSame('Lagos Auto Studio', $business->name);
        $this->assertSame('Auto detailing', $business->category);
        $this->assertSame('hello@lagosauto.test', $business->email);
        $this->assertSame('+234 800 000 1111', $business->phone);
        $this->assertSame('https://lagosauto.test', $business->website);
        $this->assertSame('Premium mobile and studio detailing.', $business->description);
    }

    private function createBusiness(User $owner, string $name = 'Lagos Detailing'): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => str($name)->slug().'-settings-test',
            'category' => 'Auto care',
            'email' => str($name)->slug().'@example.test',
        ]);

        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }
}
