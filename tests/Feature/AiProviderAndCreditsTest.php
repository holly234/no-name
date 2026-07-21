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
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\AiCreditLedgerService;
use App\Services\AiPromptBuilder;
use App\Services\AiReplyRecoveryService;
use App\Services\GeminiAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiProviderAndCreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_gemini_provider_parses_structured_reply_and_usage(): void
    {
        config([
            'ai.providers.gemini.api_key' => 'test-key',
            'ai.providers.gemini.model' => 'gemini-3.1-flash-lite',
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

    public function test_gemini_provider_includes_the_provider_error_message(): void
    {
        config([
            'ai.providers.gemini.api_key' => 'test-key',
            'ai.providers.gemini.model' => 'retired-model',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'This model is no longer available.'],
            ], 404),
        ]);

        $this->expectExceptionMessage('Gemini request failed with HTTP 404: This model is no longer available.');

        app(GeminiAiProvider::class)->generate(new AiPrompt('System rules', 'Hello'));
    }

    public function test_ai_prompt_encourages_useful_general_answers_without_inventing_business_facts(): void
    {
        [, $conversation, $incoming] = $this->conversation();

        $prompt = app(AiPromptBuilder::class)->build($conversation, $incoming->body);

        $this->assertStringContainsString('low-risk general-knowledge questions', $prompt->system);
        $this->assertStringContainsString('Missing knowledge or moderate uncertainty alone is not a reason to hand over', $prompt->system);
        $this->assertStringContainsString('Never invent business-specific facts', $prompt->system);
        $this->assertStringContainsString('ask one concise clarifying question', $prompt->system);
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
                    model: 'gemini-3.1-flash-lite',
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

    public function test_ai_job_delivers_a_natural_two_part_chat_reply_as_two_messages(): void
    {
        [$business, $conversation, $incoming] = $this->conversation();
        app(AiCreditLedgerService::class)->grant($business, 100, 'Beta grant');
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function generate(AiPrompt $prompt): AiGeneration
            {
                return new AiGeneration(
                    reply: 'Yes, we offer interior detailing.|||What day would you like to come in?',
                    state: Conversation::STATE_WAITING,
                    confidence: 0.95,
                    requiresHuman: false,
                    reason: null,
                    intent: 'booking',
                    provider: 'gemini',
                    model: 'gemini-3.1-flash-lite',
                    inputTokens: 100,
                    outputTokens: 20,
                    providerCostUsd: 0,
                    latencyMs: 90,
                );
            }
        });

        app()->call([new ProcessAiReply($incoming->id), 'handle']);

        $replies = $conversation->messages()->where('sender_type', 'ai')->orderBy('id')->pluck('body')->all();
        $this->assertSame([
            'Yes, we offer interior detailing.',
            'What day would you like to come in?',
        ], $replies);
    }

    public function test_ai_job_transcribes_a_voice_note_and_uses_it_as_the_customer_message(): void
    {
        config([
            'ai.providers.gemini.api_key' => 'test-key',
            'ai.providers.gemini.model' => 'gemini-3.1-flash-lite',
        ]);
        Storage::fake('local');
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Can I book for Friday morning?']]]]],
            ]),
        ]);
        [$business, $conversation, $incoming] = $this->conversation();
        $incoming->update(['body' => '[Voice note]']);
        Storage::disk('local')->put('voice/test.ogg', 'fake-audio');
        MessageAttachment::create([
            'message_id' => $incoming->id,
            'business_id' => $business->id,
            'provider' => 'telegram',
            'provider_attachment_id' => 'voice-file-1',
            'filename' => 'voice.ogg',
            'mime_type' => 'audio/ogg',
            'size' => 10,
            'disk' => 'local',
            'storage_path' => 'voice/test.ogg',
            'metadata' => ['media_type' => 'voice'],
        ]);
        app(AiCreditLedgerService::class)->grant($business, 100, 'Beta grant');
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function generate(AiPrompt $prompt): AiGeneration
            {
                if (! str_contains($prompt->message, 'Can I book for Friday morning?')) {
                    throw new \RuntimeException('The transcript was not passed to the AI prompt.');
                }

                return new AiGeneration(
                    reply: 'Yes. What time on Friday morning?',
                    state: Conversation::STATE_WAITING,
                    confidence: 0.9,
                    requiresHuman: false,
                    reason: null,
                    intent: 'booking',
                    provider: 'gemini',
                    model: 'gemini-3.1-flash-lite',
                    inputTokens: 100,
                    outputTokens: 12,
                    providerCostUsd: 0,
                    latencyMs: 80,
                );
            }
        });

        app()->call([new ProcessAiReply($incoming->id), 'handle']);

        $this->assertSame('Can I book for Friday morning?', $incoming->fresh()->metadata['transcription']);
        Http::assertSent(fn ($request) => data_get($request->data(), 'contents.0.parts.1.inlineData.mimeType') === 'audio/ogg');
    }

    public function test_ai_job_sends_the_generated_acknowledgement_before_handover(): void
    {
        [$business, $conversation, $incoming] = $this->conversation();
        app(AiCreditLedgerService::class)->grant($business, 100, 'Beta grant');
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function generate(AiPrompt $prompt): AiGeneration
            {
                return new AiGeneration(
                    reply: 'Thanks — I have passed this to a teammate who can confirm the exact price.',
                    state: Conversation::STATE_NEEDS_HUMAN,
                    confidence: 0.72,
                    requiresHuman: true,
                    reason: 'Price requires staff confirmation.',
                    intent: 'pricing',
                    provider: 'gemini',
                    model: 'gemini-3.1-flash-lite',
                    inputTokens: 120,
                    outputTokens: 18,
                    providerCostUsd: 0,
                    latencyMs: 100,
                );
            }
        });

        app()->call([new ProcessAiReply($incoming->id), 'handle']);

        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->fresh()->status);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'body' => 'Thanks — I have passed this to a teammate who can confirm the exact price.',
        ]);
    }

    public function test_ai_job_uses_a_safe_fallback_so_handover_is_never_silent(): void
    {
        [$business, $conversation, $incoming] = $this->conversation();
        AiSetting::where('business_id', $business->id)->update([
            'fallback_response' => 'I’m checking this with the team. Someone will reply here shortly.',
        ]);
        app(AiCreditLedgerService::class)->grant($business, 100, 'Beta grant');
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function generate(AiPrompt $prompt): AiGeneration
            {
                return new AiGeneration(
                    reply: '',
                    state: Conversation::STATE_NEEDS_HUMAN,
                    confidence: 0.4,
                    requiresHuman: true,
                    reason: 'Staff confirmation required.',
                    intent: 'handover',
                    provider: 'gemini',
                    model: 'gemini-3.1-flash-lite',
                    inputTokens: 100,
                    outputTokens: 0,
                    providerCostUsd: 0,
                    latencyMs: 80,
                );
            }
        });

        app()->call([new ProcessAiReply($incoming->id), 'handle']);

        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->fresh()->status);
        $this->assertTrue($conversation->messages()
            ->where('sender_type', 'ai')
            ->where('body', 'I’m checking this with the team. Someone will reply here shortly.')
            ->exists());
    }

    public function test_ai_job_routes_to_human_when_credits_are_empty(): void
    {
        [, $conversation, $incoming] = $this->conversation();

        app()->call([new ProcessAiReply($incoming->id), 'handle']);

        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->fresh()->status);
        $this->assertSame(0, AiUsageRecord::count());
        $this->assertFalse($conversation->messages()->where('sender_type', 'ai')->exists());
    }

    public function test_unanswered_ai_controlled_handover_is_automatically_requeued(): void
    {
        Bus::fake();
        Cache::flush();
        [, $conversation, $incoming] = $this->conversation();
        $conversation->update([
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'auto',
        ]);
        $incoming->forceFill(['created_at' => now()->subMinutes(2)])->save();

        $queued = app(AiReplyRecoveryService::class)->recover();

        $this->assertSame(1, $queued);
        Bus::assertDispatched(ProcessAiReply::class, fn (ProcessAiReply $job) => $job->messageId === $incoming->id && $job->recovery);
        $this->assertDatabaseHas('automation_logs', [
            'business_id' => $conversation->business_id,
            'event_type' => 'ai_reply_recovery_queued',
        ]);
    }

    public function test_recovery_never_touches_human_controlled_conversations(): void
    {
        Bus::fake();
        Cache::flush();
        [, $conversation, $incoming] = $this->conversation();
        $conversation->update([
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
        ]);
        $incoming->forceFill(['created_at' => now()->subMinutes(2)])->save();

        $this->assertSame(0, app(AiReplyRecoveryService::class)->recover());
        Bus::assertNotDispatched(ProcessAiReply::class);
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
