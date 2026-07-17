<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_only_offers_google_authentication(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertDontSee('Forgot your password?')
            ->assertDontSee('name="password"', false);

        $this->get('/register')->assertRedirect('/login');
        $this->post('/login', [])->assertMethodNotAllowed();
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_new_google_user_is_created_and_sent_to_workspace_onboarding(): void
    {
        $this->mockGoogleUser('google-123', 'New@Example.com', 'New Owner');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('onboarding.workspace'));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->last_login_at);
        $this->assertNotSame('', $user->getAuthPassword());
    }

    public function test_existing_email_is_safely_linked_and_sent_to_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'google_id' => null]);
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Owner Workspace',
            'slug' => 'owner-workspace',
            'webhook_secret' => 'test-secret',
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        $this->mockGoogleUser('google-existing', 'owner@example.com', 'Owner Updated');

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-existing', $user->refresh()->google_id);
        $this->assertSame(1, User::query()->where('email', 'owner@example.com')->count());
    }

    public function test_google_callback_failure_returns_to_login_without_authenticating(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andThrow(new \RuntimeException('OAuth failed'));
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    private function mockGoogleUser(string $id, string $email, string $name): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn($id);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn($name);
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.test/avatar.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
    }
}
