<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Data\AiGeneration;
use App\Data\AiPrompt;
use App\Exceptions\InsufficientAiCredits;
use App\Jobs\ProcessAiReply;
use App\Models\AiCreditTransaction;
use App\Models\AiCreditWallet;
use App\Models\AiSetting;
use App\Models\AiUsageRecord;
use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use App\Services\AiCreditLedgerService;
use App\Services\GeminiAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderAndCreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_gemini_provider_parses_structured_reply_and_usage(): void
    {
        config([
            'ai.providers.gemini.api_key' => 'test-key',
            'ai.providers.gemini.model' => 'gemini-2.5-flash-lite',
            'ai.providers.gemini.billing_mode' => 'free',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'reply' => 'Yes, we are available tomorrow.',
                        'state' => Conversation::STATE_WAITING,
                        'confidence' => 0.91,
                        'requires_human' => false,
                        'reason' => null,
                        'intent' => 'availability',
                    ])]]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => ['promptTokenCount' => 120, 'candidatesTokenCount' => 24],
            ]),
        ]);

        $result = app(GeminiAiProvider::class)->generate(new AiPrompt('System rules', 'Are you open tomorrow?'));

        $this->assertSame('Yes, we are available tomorrow.', $result->reply);
        $this->assertSame(Conversation::STATE_WAITING, $result->state);
        $this->assertSame(120, $result->inputTokens);
        $this->assertSame(24, $result->outputTokens);
        $this->assertSame(0.0, $result->providerCostUsd);
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key'));
    }

    public function test_credit_ledger_reserves_settles_and_releases_atomically(): void
    {
        $business = $this->business();
        $ledger = app(AiCreditLedgerService::class);
        $ledger->grant($business, 100, 'Beta grant', 'grant-1');

        $reservation = $ledger->reserve($business, 25);
        $this->assertSame(75, AiCreditWallet::where('business_id', $business->id)->value('balance'));

        $charged = $ledger->settle($business, $reservation, 4);
        $this->assertSame(4, $charged);
        $this->assertSame(96, AiCreditWallet::where('business_id', $business->id)->value('balance'));
        $this->assertSame(4, AiCreditWallet::where('business_id', $business->id)->value('lifetime_used'));
        $this->assertSame(4, $ledger->settle($business, $reservation, 4));

        $second = $ledger->reserve($business, 25);
        $ledger->release($business, $second, 'provider_failed');
        $this->assertSame(96, AiCreditWallet::where('business_id', $business->id)->value('balance'));
        $this->assertSame(1, AiCreditTransaction::where('type', 'reservation_release')->count());
    }

    public function test_reservation_fails_without_enough_credits(): void
    {
        $this->expectException(InsufficientAiCredits::class);

        app(AiCreditLedgerService::class)->reserve($this->business(), 25);
    }

    public function test_ai_job_generates_reply_and_records_usage(): void
    {
        config(['ai.tokens_per_credit' => 100]);
        [$business, $conversation, $incoming] = $this->conversation();
        app(AiCreditLedgerService::class)->grant($business, 100, 'Beta grant');
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function generate(AiPrompt $prompt): AiGeneration
            {
                return new AiGeneration(
                    reply: 'You can book for tomorrow.',
                    state: Conversation::STATE_WAITING,
                    confidence: 0.93,
                    requiresHuman: false,
                    reason: null,
                    intent: 'booking',
                    provider: 'gemini',
                    model: 'gemini-2.5-flash-lite',
                    inputTokens: 180,
                    outputTokens: 20,
                    providerCostUsd: 0,
                    latencyMs: 120,
                );
            }
        });

        app()->call([new ProcessAiReply($incoming->id), 'handle']);

        $this->assertTrue($conversation->messages()->where('sender_type', 'ai')->where('body', 'You can book for tomorrow.')->exists());
        $this->assertSame(Conversation::STATE_WAITING, $conversation->fresh()->status);
        $this->assertDatabaseHas('ai_usage_records', [
            'message_id' => $incoming->id,
            'status' => 'completed',
            'credits_used' => 2,
        ]);
        $this->assertSame(98, AiCreditWallet::where('business_id', $business->id)->value('balance'));
    }

    public function test_ai_job_routes_to_human_when_credits_are_empty(): void
    {
        [, $conversation, $incoming] = $this->conversation();

        app()->call([new ProcessAiReply($incoming->id), 'handle']);

        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->fresh()->status);
        $this->assertSame(0, AiUsageRecord::count());
        $this->assertFalse($conversation->messages()->where('sender_type', 'ai')->exists());
    }

    private function business(): Business
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'AI Test Workspace',
            'slug' => 'ai-test-'.str()->random(5),
            'email' => $owner->email,
        ]);
        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }

    private function conversation(): array
    {
        $business = $this->business();
        AiSetting::create([
            'business_id' => $business->id,
            'auto_reply_enabled' => true,
            'human_takeover_enabled' => true,
        ]);
        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Demo Instagram',
            'external_account_id' => 'demo-instagram',
            'status' => 'connected',
        ]);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Ada',
            'external_id' => '@ada',
            'channel' => 'Instagram',
        ]);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_external_id' => $customer->external_id,
            'channel' => 'Instagram',
            'status' => Conversation::STATE_AI_HANDLING,
            'ai_mode' => 'auto',
        ]);
        $incoming = Message::create([
            'business_id' => $business->id,
            'conversation_id' => $conversation->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Can I book tomorrow?',
        ]);

        return [$business, $conversation, $incoming];
    }
}
