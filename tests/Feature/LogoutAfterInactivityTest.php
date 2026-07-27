<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutAfterInactivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_is_logged_out_after_ten_minutes_of_inactivity(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['auth.last_activity' => now()->subMinutes(10)])
            ->get('/onboarding/workspace');

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'You were signed out after 10 minutes of inactivity.');

        $this->assertGuest();
    }

    public function test_inbox_polling_does_not_refresh_user_activity(): void
    {
        $user = User::factory()->create();
        $lastActivity = now()->subMinutes(5);

        $this
            ->actingAs($user)
            ->withSession(['auth.last_activity' => $lastActivity])
            ->get(route('dashboard.inbox.pulse'));

        $this->assertEquals(
            $lastActivity->timestamp,
            session('auth.last_activity')->timestamp,
        );
    }
}
