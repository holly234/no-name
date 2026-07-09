<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_to_a_business_they_belong_to(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user, 'Lagos Detailing');

        $response = $this
            ->actingAs($user)
            ->post(route('workspace.switch'), [
                'business_id' => $business->id,
            ]);

        $response->assertRedirect();
        $this->assertSame($business->id, session('current_business_id'));
    }

    public function test_user_cannot_switch_to_another_users_business(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $foreignBusiness = $this->createBusiness($owner, 'VIP Rentals');

        $response = $this
            ->actingAs($user)
            ->post(route('workspace.switch'), [
                'business_id' => $foreignBusiness->id,
            ]);

        $response->assertForbidden();
        $this->assertNotSame($foreignBusiness->id, session('current_business_id'));
    }

    private function createBusiness(User $owner, string $name): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => str($name)->slug().'-test',
            'category' => 'Demo',
            'email' => str($name)->slug().'@example.test',
        ]);

        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }
}
