<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_defaults_to_inbox(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Lagos Detailing',
            'slug' => 'lagos-detailing-test',
            'category' => 'Auto care',
            'email' => 'lagos-detailing@example.test',
        ]);

        $business->users()->attach($user->id, ['role' => 'Owner']);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertRedirect(route('dashboard.inbox'));
    }
}
