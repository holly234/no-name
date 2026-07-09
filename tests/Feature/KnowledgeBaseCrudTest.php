<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessRule;
use App\Models\Faq;
use App\Models\Product;
use App\Models\SavedReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_update_and_delete_faqs_for_current_business(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);

        $this->actingAs($user)
            ->post(route('dashboard.knowledge-base.faqs.store'), [
                'question' => 'Do you work weekends?',
                'answer' => 'Yes, weekend bookings are available.',
                'category' => 'Booking',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'FAQ added.');

        $faq = Faq::where('business_id', $business->id)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('dashboard.knowledge-base.faqs.update', $faq), [
                'question' => 'Do you work Sundays?',
                'answer' => 'Sunday bookings are available by appointment.',
                'category' => 'Schedule',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'FAQ updated.');

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'business_id' => $business->id,
            'question' => 'Do you work Sundays?',
            'category' => 'Schedule',
        ]);

        $this->actingAs($user)
            ->delete(route('dashboard.knowledge-base.faqs.destroy', $faq))
            ->assertRedirect()
            ->assertSessionHas('status', 'FAQ deleted.');

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_user_can_manage_products_rules_and_saved_replies(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);

        $this->actingAs($user)
            ->post(route('dashboard.knowledge-base.products.store'), [
                'name' => 'Ceramic coating',
                'price' => 'From NGN 150,000',
                'availability' => 'Weekdays',
                'description' => 'Paint protection service.',
                'ai_notes' => 'Ask for vehicle size before final quote.',
            ])
            ->assertRedirect();

        $product = Product::where('business_id', $business->id)->firstOrFail();
        $this->actingAs($user)
            ->patch(route('dashboard.knowledge-base.products.update', $product), [
                'name' => 'Ceramic coating pro',
                'price' => 'From NGN 180,000',
                'availability' => 'Weekdays',
                'description' => 'Longer paint protection service.',
                'ai_notes' => 'Confirm vehicle size.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Ceramic coating pro',
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.knowledge-base.rules.store'), [
                'rule_type' => 'pricing',
                'title' => 'No final quote without photos',
                'content' => 'Ask for photos before giving a final quote.',
            ])
            ->assertRedirect();

        $rule = BusinessRule::where('business_id', $business->id)->firstOrFail();
        $this->actingAs($user)
            ->patch(route('dashboard.knowledge-base.rules.update', $rule), [
                'rule_type' => 'policy',
                'title' => 'Photos required',
                'content' => 'Photos are required before final quotes.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('business_rules', [
            'id' => $rule->id,
            'rule_type' => 'policy',
            'title' => 'Photos required',
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.knowledge-base.saved-replies.store'), [
                'title' => 'Ask for location',
                'shortcut' => '/location',
                'body' => 'Please share your location so we can confirm availability.',
            ])
            ->assertRedirect();

        $reply = SavedReply::where('business_id', $business->id)->firstOrFail();
        $this->actingAs($user)
            ->patch(route('dashboard.knowledge-base.saved-replies.update', $reply), [
                'title' => 'Ask for address',
                'shortcut' => '/address',
                'body' => 'Please share your address so we can confirm availability.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('saved_replies', [
            'id' => $reply->id,
            'title' => 'Ask for address',
            'shortcut' => '/address',
        ]);

        $this->actingAs($user)->delete(route('dashboard.knowledge-base.products.destroy', $product))->assertRedirect();
        $this->actingAs($user)->delete(route('dashboard.knowledge-base.rules.destroy', $rule))->assertRedirect();
        $this->actingAs($user)->delete(route('dashboard.knowledge-base.saved-replies.destroy', $reply))->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('business_rules', ['id' => $rule->id]);
        $this->assertDatabaseMissing('saved_replies', ['id' => $reply->id]);
    }

    public function test_user_cannot_mutate_another_workspace_knowledge_base(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $this->createBusiness($user, 'Lagos Detailing');
        $foreignBusiness = $this->createBusiness($owner, 'VIP Rentals');
        $foreignFaq = Faq::create([
            'business_id' => $foreignBusiness->id,
            'question' => 'Foreign question',
            'answer' => 'Foreign answer',
        ]);

        $this->actingAs($user)
            ->patch(route('dashboard.knowledge-base.faqs.update', $foreignFaq), [
                'question' => 'Changed',
                'answer' => 'Changed',
                'category' => null,
            ])
            ->assertForbidden();

        $this->assertSame('Foreign question', $foreignFaq->fresh()->question);
    }

    public function test_knowledge_base_page_shows_editable_sections(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $this->createBusiness($user);

        $response = $this->actingAs($user)->get(route('dashboard.knowledge-base'));

        $response->assertOk();
        $response->assertSee('Add FAQ');
        $response->assertDontSee('Add product/service');

        $this->actingAs($user)
            ->get(route('dashboard.knowledge-base', ['section' => 'products']))
            ->assertOk()
            ->assertSee('Add product/service');

        $this->actingAs($user)
            ->get(route('dashboard.knowledge-base', ['section' => 'rules']))
            ->assertOk()
            ->assertSee('Add rule');

        $this->actingAs($user)
            ->get(route('dashboard.knowledge-base', ['section' => 'saved-replies']))
            ->assertOk()
            ->assertSee('Add saved reply');
    }

    private function createBusiness(User $owner, string $name = 'Lagos Detailing'): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => str($name)->slug().'-kb-test',
            'category' => 'Auto care',
            'email' => str($name)->slug().'@example.test',
        ]);

        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }
}
