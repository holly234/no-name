<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSettingsBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_human_takeover_is_always_available_as_a_safety_control(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        AiSetting::create([
            'business_id' => $business->id,
            'auto_reply_enabled' => true,
            'human_takeover_enabled' => false,
        ]);
        $conversation = $this->createConversation($business);

        $response = $this->actingAs($user)->get(route('dashboard.inbox', [
            'conversation' => $conversation->id,
        ]));

        $response->assertOk();
        $response->assertDontSee('Pause automation unavailable');
        $response->assertSee(route('dashboard.inbox.take-over', $conversation), false);

        $this->actingAs($user)
            ->post(route('dashboard.inbox.take-over', $conversation))
            ->assertRedirect()
            ->assertSessionHas('status', 'Human takeover enabled.');

        $conversation->refresh();
        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->status);
        $this->assertSame('human', $conversation->ai_mode);
    }

    public function test_disabled_auto_reply_prevents_ai_response_generation(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        AiSetting::create([
            'business_id' => $business->id,
            'auto_reply_enabled' => false,
            'human_takeover_enabled' => true,
        ]);

        $conversation = app(MessageIngestionService::class)->ingest([
            'business_id' => $business->id,
            'channel' => 'Instagram',
            'external_account_id' => 'instagram-main',
            'customer_external_id' => 'customer-one',
            'customer_name' => 'Customer One',
            'body' => 'Can I book tomorrow?',
            'confidence' => 0.95,
        ]);

        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->status);
        $this->assertSame('human', $conversation->ai_mode);
        $this->assertSame(1, $conversation->messages()->count());
        $this->assertFalse($conversation->messages()->where('sender_type', 'ai')->exists());
    }

    public function test_business_hours_enabled_prevents_ai_replies_outside_default_reply_window(): void
    {
        $this->travelTo(now()->setTime(22, 0));

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        AiSetting::create([
            'business_id' => $business->id,
            'auto_reply_enabled' => true,
            'human_takeover_enabled' => true,
            'business_hours_enabled' => true,
        ]);

        $conversation = app(MessageIngestionService::class)->ingest([
            'business_id' => $business->id,
            'channel' => 'WhatsApp',
            'external_account_id' => 'whatsapp-main',
            'customer_external_id' => 'customer-two',
            'customer_name' => 'Customer Two',
            'body' => 'Can I book tomorrow?',
            'confidence' => 0.95,
        ]);

        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->status);
        $this->assertSame('human', $conversation->ai_mode);
        $this->assertFalse($conversation->messages()->where('sender_type', 'ai')->exists());
    }

    public function test_ai_escalated_conversations_stay_in_auto_mode_by_default(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        AiSetting::create([
            'business_id' => $business->id,
            'auto_reply_enabled' => true,
            'human_takeover_enabled' => true,
        ]);

        $conversation = app(MessageIngestionService::class)->ingest([
            'business_id' => $business->id,
            'channel' => 'Instagram',
            'external_account_id' => 'instagram-main',
            'customer_external_id' => '@customerthree',
            'customer_name' => 'Customer Three',
            'body' => 'I want to file a complaint about the service.',
            'confidence' => 0.95,
        ]);

        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->status);
        $this->assertSame('auto', $conversation->ai_mode);
        $this->assertFalse($conversation->isHumanControlled());
    }

    public function test_resuming_ai_does_not_send_an_immediate_placeholder_reply(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        AiSetting::create([
            'business_id' => $business->id,
            'auto_reply_enabled' => true,
            'human_takeover_enabled' => true,
        ]);
        $conversation = $this->createConversation($business);
        $conversation->update([
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
        ]);
        $messageCount = $conversation->messages()->count();

        $this->actingAs($user)
            ->post(route('dashboard.inbox.resume-ai', $conversation))
            ->assertRedirect()
            ->assertSessionHas('status', 'AI resumed.');

        $conversation->refresh();
        $this->assertSame(Conversation::STATE_AI_HANDLING, $conversation->status);
        $this->assertSame('auto', $conversation->ai_mode);
        $this->assertSame($messageCount, $conversation->messages()->count());
        $this->assertFalse($conversation->messages()->where('sender_type', 'ai')->exists());
    }

    public function test_manual_staff_reply_switches_conversation_to_human_mode(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        AiSetting::create([
            'business_id' => $business->id,
            'auto_reply_enabled' => true,
            'human_takeover_enabled' => true,
        ]);
        $conversation = $this->createConversation($business);

        $this->actingAs($user)
            ->post(route('dashboard.inbox.reply', $conversation), [
                'body' => 'I will handle this from here.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Reply sent.');

        $conversation->refresh();
        $this->assertSame(Conversation::STATE_WAITING, $conversation->status);
        $this->assertSame('human', $conversation->ai_mode);
        $this->assertTrue($conversation->isHumanControlled());
        $this->assertTrue($conversation->messages()->where([
            'sender_type' => 'human',
            'body' => 'I will handle this from here.',
        ])->exists());
    }

    private function createBusiness(User $owner): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Lagos Detailing',
            'slug' => 'lagos-detailing-test',
            'category' => 'Auto care',
            'email' => 'lagos-detailing@example.test',
        ]);

        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }

    private function createConversation(Business $business): Conversation
    {
        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Lagos Instagram',
            'external_account_id' => 'lagos-instagram-main',
            'status' => 'connected',
            'connected_at' => now(),
        ]);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Customer One',
            'external_id' => 'customer-one',
            'channel' => 'Instagram',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_external_id' => $customer->external_id,
            'channel' => 'Instagram',
            'status' => Conversation::STATE_WAITING,
            'ai_mode' => 'auto',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Hello',
        ]);

        return $conversation;
    }
}
