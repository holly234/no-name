<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\MessageAttachment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gmail_redirect_requires_authentication(): void
    {
        $this->get(route('dashboard.accounts.gmail.redirect'))
            ->assertRedirect(route('login'));
    }

    public function test_gmail_callback_creates_connected_account_for_current_business(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        config([
            'services.gmail.client_id' => 'gmail-client-id',
            'services.gmail.client_secret' => 'gmail-client-secret',
            'services.gmail.redirect_uri' => 'http://localhost/dashboard/accounts/gmail/callback',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'gmail-access-token',
                'refresh_token' => 'gmail-refresh-token',
                'expires_in' => 3600,
                'scope' => 'https://www.googleapis.com/auth/gmail.readonly',
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/profile' => Http::response([
                'emailAddress' => 'support@example.com',
                'messagesTotal' => 10,
                'historyId' => 'history-1',
            ]),
        ]);

        $this->actingAs($user)
            ->withSession([
                'current_business_id' => $business->id,
                'gmail_oauth_state' => 'state-token',
                'gmail_oauth_business_id' => $business->id,
            ])
            ->get(route('dashboard.accounts.gmail.callback', [
                'code' => 'auth-code',
                'state' => 'state-token',
            ]))
            ->assertRedirect(route('dashboard.accounts'));

        $account = ConnectedAccount::where('business_id', $business->id)
            ->where('platform', 'gmail')
            ->firstOrFail();

        $this->assertSame('support@example.com', $account->account_name);
        $this->assertSame('support@example.com', $account->external_account_id);
        $this->assertSame('gmail-access-token', $account->access_token);
        $this->assertSame('gmail-refresh-token', $account->refresh_token);
        $this->assertSame('connected', $account->status);
        $this->assertNotSame(
            'gmail-access-token',
            DB::table('connected_accounts')->where('id', $account->id)->value('access_token')
        );
    }

    public function test_gmail_callback_registers_watch_when_pubsub_topic_is_configured(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        config([
            'services.gmail.client_id' => 'gmail-client-id',
            'services.gmail.client_secret' => 'gmail-client-secret',
            'services.gmail.redirect_uri' => 'http://localhost/dashboard/accounts/gmail/callback',
            'services.gmail.pubsub_topic' => 'projects/perpetual/topics/gmail-inbound',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'gmail-access-token',
                'refresh_token' => 'gmail-refresh-token',
                'expires_in' => 3600,
                'scope' => 'https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.send',
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/profile' => Http::response([
                'emailAddress' => 'support@example.com',
                'messagesTotal' => 10,
                'historyId' => 'history-1',
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/watch' => Http::response([
                'historyId' => 'history-watch',
                'expiration' => '1784160000000',
            ]),
        ]);

        $this->actingAs($user)
            ->withSession([
                'current_business_id' => $business->id,
                'gmail_oauth_state' => 'state-token',
                'gmail_oauth_business_id' => $business->id,
            ])
            ->get(route('dashboard.accounts.gmail.callback', [
                'code' => 'auth-code',
                'state' => 'state-token',
            ]))
            ->assertRedirect(route('dashboard.accounts'));

        Http::assertSent(fn ($request) => $request->url() === 'https://gmail.googleapis.com/gmail/v1/users/me/watch'
            && $request['topicName'] === 'projects/perpetual/topics/gmail-inbound'
            && $request['labelIds'] === ['INBOX']);

        $account = ConnectedAccount::where('platform', 'gmail')->firstOrFail();

        $this->assertSame('history-watch', $account->provider_meta['watch_history_id']);
        $this->assertSame('projects/perpetual/topics/gmail-inbound', $account->provider_meta['watch_topic']);
    }

    public function test_gmail_sync_rejects_foreign_business_account(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $this->createBusiness($user, 'Lagos Detailing');
        $foreignBusiness = $this->createBusiness($owner, 'VIP Rentals');
        $account = $this->createGmailAccount($foreignBusiness);

        $this->actingAs($user)
            ->post(route('dashboard.accounts.gmail.sync', $account))
            ->assertForbidden();
    }

    public function test_gmail_sync_imports_messages_into_current_business_only(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createGmailAccount($business);
        $this->fakeGmailSync('msg-1', 'thread-1', 'Ada Customer <ada@example.com>', 'Need pricing', 'Please send your pricing.');

        $this->actingAs($user)
            ->post(route('dashboard.accounts.gmail.sync', $account))
            ->assertRedirect()
            ->assertSessionHas('status', 'Inbox sync complete: 1 imported, 0 skipped.');

        $conversation = Conversation::where('business_id', $business->id)
            ->where('channel', 'Gmail')
            ->firstOrFail();

        $this->assertSame($account->id, $conversation->connected_account_id);
        $this->assertSame('Ada Customer', $conversation->customer_name);
        $this->assertSame('ada@example.com', $conversation->customer_external_id);
        $this->assertSame(Conversation::STATE_NEEDS_HUMAN, $conversation->status);
        $this->assertSame('human', $conversation->ai_mode);
        $this->assertDatabaseHas('messages', [
            'business_id' => $business->id,
            'conversation_id' => $conversation->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
        ]);
        $this->assertSame(1, Conversation::where('channel', 'Gmail')->count());
    }

    public function test_gmail_sync_does_not_import_duplicate_messages(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createGmailAccount($business);
        $this->fakeGmailSync('msg-duplicate', 'thread-duplicate', 'Kemi <kemi@example.com>', 'Hello', 'I need help.');

        $this->actingAs($user)->post(route('dashboard.accounts.gmail.sync', $account))->assertRedirect();
        $this->actingAs($user)->post(route('dashboard.accounts.gmail.sync', $account))->assertRedirect();

        $this->assertSame(1, Message::where('metadata->gmail_message_id', 'msg-duplicate')->count());
    }

    public function test_gmail_sync_stores_subject_separately_and_strips_template_html(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createGmailAccount($business);

        $this->fakeGmailSync(
            'msg-html',
            'thread-html',
            'Stake.com <promo@stake.test>',
            'Hey Hollyboyde, ready to grab your Crypto Reward?',
            "Your next Crypto Reward starts now.\n\n<!DOCTYPE html><html><head><style>.hidden{display:none}</style></head><body><p>Template copy</p></body></html>"
        );

        $this->actingAs($user)->post(route('dashboard.accounts.gmail.sync', $account))->assertRedirect();

        $message = Message::where('metadata->gmail_message_id', 'msg-html')->firstOrFail();

        $this->assertSame('Your next Crypto Reward starts now.', $message->body);
        $this->assertSame('Hey Hollyboyde, ready to grab your Crypto Reward?', $message->metadata['subject']);
        $this->assertStringNotContainsString('Subject:', $message->body);
        $this->assertStringNotContainsString('<!DOCTYPE', $message->body);
        $this->assertStringNotContainsString('<style>', $message->body);
    }

    public function test_gmail_sync_collapses_excessive_template_spacing_and_quoted_replies(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createGmailAccount($business);

        $this->fakeGmailSync(
            'msg-spaced',
            'thread-spaced',
            'OPay <no-reply@opay.test>',
            'Transfer successful',
            "Dear OLAMIDE,\n\n\n\n\nYour transfer of\n\n\n₦3,500.00\n\n\nis successful.\n\n\n\n\nTransfer Details:\n\nOn Tue, Jul 7, 2026 at 7:22 PM Someone <someone@example.com> wrote:\n> Hi\n>"
        );

        $this->actingAs($user)->post(route('dashboard.accounts.gmail.sync', $account))->assertRedirect();

        $message = Message::where('metadata->gmail_message_id', 'msg-spaced')->firstOrFail();

        $this->assertSame(
            "Dear OLAMIDE,\n\nYour transfer of\n\n₦3,500.00\n\nis successful.\n\nTransfer Details:",
            $message->body
        );
        $this->assertStringNotContainsString("\n\n\n", $message->body);
        $this->assertStringNotContainsString('wrote:', $message->body);
        $this->assertStringNotContainsString('> Hi', $message->body);
    }

    public function test_gmail_message_urls_render_as_safe_clickable_links(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createGmailAccount($business);

        $this->fakeGmailSync(
            'msg-link',
            'thread-link',
            'Customer <customer@example.com>',
            'Website',
            'Please check https://example.com/path?tab=deposit&currency=btc. <script>alert("x")</script>'
        );

        $this->actingAs($user)->post(route('dashboard.accounts.gmail.sync', $account))->assertRedirect();

        $conversation = Conversation::where('business_id', $business->id)
            ->where('channel', 'Gmail')
            ->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard.inbox', [
            'conversation' => $conversation->id,
        ]));

        $response->assertOk();
        $response->assertSee('href="https://example.com/path?tab=deposit&amp;currency=btc"', false);
        $response->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', false);
        $response->assertDontSee('<script>alert("x")</script>', false);
    }

    public function test_no_reply_gmail_threads_disable_replies_in_ui_and_controller(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createGmailAccount($business);

        $this->fakeGmailSync(
            'msg-no-reply',
            'thread-no-reply',
            'No Reply <no-reply@mailer.test>',
            'Your receipt',
            'This is an automated receipt.',
            [
                ['name' => 'Auto-Submitted', 'value' => 'auto-generated'],
            ]
        );

        $this->actingAs($user)->post(route('dashboard.accounts.gmail.sync', $account))->assertRedirect();

        $message = Message::where('metadata->gmail_message_id', 'msg-no-reply')->firstOrFail();
        $conversation = $message->conversation;

        $this->assertTrue($message->metadata['reply_disabled']);
        $this->assertSame('Automated sender', $message->metadata['reply_disabled_reason']);

        $response = $this->actingAs($user)->get(route('dashboard.inbox', [
            'conversation' => $conversation->id,
        ]));

        $response->assertOk();
        $response->assertSee('Replies disabled');
        $response->assertSee('Mark reviewed');
        $response->assertDontSee('placeholder="Message"', false);

        $this->actingAs($user)
            ->post(route('dashboard.inbox.reply', $conversation), ['body' => 'Trying to reply.'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Replies are disabled for this email thread because it looks automated or not replyable.');

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'direction' => 'outgoing',
            'body' => 'Trying to reply.',
        ]);
    }

    public function test_legacy_noreply_gmail_threads_still_disable_replies_after_outgoing_message(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createGmailAccount($business);

        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_name' => 'Stake.com',
            'customer_external_id' => 'noreply@mail.stake.com',
            'channel' => 'Gmail',
            'status' => Conversation::STATE_WAITING,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Promotional email body.',
            'metadata' => [
                'source' => 'gmail',
                'from_email' => 'noreply@mail.stake.com',
                'to_email' => 'support@example.com',
                'subject' => 'Crypto reward',
            ],
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'outgoing',
            'sender_type' => 'human',
            'body' => 'Accidental reply.',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.inbox', [
            'conversation' => $conversation->id,
        ]));

        $response->assertOk();
        $response->assertSee('Replies disabled');
        $response->assertSee('No reply');
        $response->assertDontSee('placeholder="Message"', false);

        $this->actingAs($user)
            ->post(route('dashboard.inbox.reply', $conversation), ['body' => 'Another accidental reply.'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Replies are disabled for this email thread because it looks automated or not replyable.');

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'body' => 'Another accidental reply.',
        ]);
    }

    public function test_gmail_sync_imports_document_and_image_attachments_and_skips_audio_and_video(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $account = $this->createGmailAccount($business);

        $this->fakeGmailSyncWithAttachments('msg-attachments', 'thread-attachments', [
            [
                'attachment_id' => 'pdf-1',
                'filename' => 'invoice.pdf',
                'mime_type' => 'application/pdf',
                'contents' => '%PDF fake invoice',
            ],
            [
                'attachment_id' => 'image-1',
                'filename' => 'photo.png',
                'mime_type' => 'image/png',
                'contents' => 'fake image',
            ],
            [
                'attachment_id' => 'audio-1',
                'filename' => 'voice.mp3',
                'mime_type' => 'audio/mpeg',
                'contents' => 'fake audio',
            ],
            [
                'attachment_id' => 'video-1',
                'filename' => 'clip.mp4',
                'mime_type' => 'video/mp4',
                'contents' => 'fake video',
            ],
        ]);

        $this->actingAs($user)->post(route('dashboard.accounts.gmail.sync', $account))->assertRedirect();

        $attachment = MessageAttachment::where('filename', 'invoice.pdf')->firstOrFail();
        $imageAttachment = MessageAttachment::where('filename', 'photo.png')->firstOrFail();

        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertSame('gmail', $attachment->provider);
        Storage::disk('local')->assertExists($attachment->storage_path);
        $this->assertSame('%PDF fake invoice', Storage::disk('local')->get($attachment->storage_path));
        $this->assertSame('image/png', $imageAttachment->mime_type);
        Storage::disk('local')->assertExists($imageAttachment->storage_path);
        $this->assertSame('fake image', Storage::disk('local')->get($imageAttachment->storage_path));
        $this->assertDatabaseMissing('message_attachments', ['filename' => 'voice.mp3']);
        $this->assertDatabaseMissing('message_attachments', ['filename' => 'clip.mp4']);
    }

    public function test_attachment_download_is_scoped_to_current_business(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $business = $this->createBusiness($owner, 'Lagos Detailing');
        $account = $this->createGmailAccount($business);
        $this->fakeGmailSyncWithAttachments('msg-download', 'thread-download', [
            [
                'attachment_id' => 'pdf-download',
                'filename' => 'quote.pdf',
                'mime_type' => 'application/pdf',
                'contents' => '%PDF quote',
            ],
        ]);
        $this->actingAs($owner)->post(route('dashboard.accounts.gmail.sync', $account))->assertRedirect();
        $attachment = MessageAttachment::where('filename', 'quote.pdf')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard.attachments.download', $attachment))
            ->assertOk()
            ->assertHeader('content-disposition');

        $foreignUser = User::factory()->create();
        $this->createBusiness($foreignUser, 'VIP Rentals');

        $this->actingAs($foreignUser)
            ->get(route('dashboard.attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_gmail_auto_sync_command_imports_connected_account_messages(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $this->createGmailAccount($business);
        $this->fakeGmailSync('msg-auto', 'thread-auto', 'Ada Customer <ada@example.com>', 'Need pricing', 'Please send your pricing.');

        $this->artisan('gmail:sync')
            ->expectsOutputToContain('support@example.com: 1 imported, 0 skipped.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('messages', [
            'business_id' => $business->id,
            'direction' => 'incoming',
            'body' => 'Please send your pricing.',
        ]);
    }

    public function test_gmail_pubsub_webhook_requires_verification_token(): void
    {
        config(['services.gmail.pubsub_verification_token' => 'pubsub-secret']);

        $this->postJson('/api/webhooks/gmail/pubsub', [
            'message' => [
                'data' => base64_encode(json_encode(['emailAddress' => 'support@example.com'])),
            ],
        ])->assertUnauthorized();
    }

    public function test_gmail_pubsub_webhook_triggers_account_sync(): void
    {
        config(['services.gmail.pubsub_verification_token' => 'pubsub-secret']);

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $this->createGmailAccount($business);
        $this->fakeGmailSync('msg-pubsub', 'thread-pubsub', 'Ada Customer <ada@example.com>', 'Need pricing', 'Please send your pricing.');

        $response = $this->postJson('/api/webhooks/gmail/pubsub?token=pubsub-secret', [
            'message' => [
                'messageId' => 'pubsub-message-1',
                'data' => base64_encode(json_encode([
                    'emailAddress' => 'support@example.com',
                    'historyId' => 'history-pubsub',
                ])),
            ],
            'subscription' => 'projects/perpetual/subscriptions/gmail-inbound',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'processed',
                'email' => 'support@example.com',
                'history_id' => 'history-pubsub',
                'message_id' => 'pubsub-message-1',
                'accounts' => 1,
                'imported' => 1,
                'failed' => 0,
            ]);

        $this->assertDatabaseHas('messages', [
            'business_id' => $business->id,
            'direction' => 'incoming',
            'body' => 'Please send your pricing.',
        ]);

        $account = ConnectedAccount::where('platform', 'gmail')->firstOrFail();

        $this->assertSame('success', $account->provider_meta['last_pubsub_status']);
        $this->assertSame('history-pubsub', $account->provider_meta['last_pubsub_history_id']);
    }

    public function test_gmail_renew_watch_command_renews_expiring_watches(): void
    {
        config(['services.gmail.pubsub_topic' => 'projects/perpetual/topics/gmail-inbound']);

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $this->createGmailAccount($business, [
            'provider_meta' => [
                'email' => 'support@example.com',
                'watch_expiration' => (string) now()->addHours(6)->valueOf(),
            ],
        ]);

        Http::fake([
            'https://gmail.googleapis.com/gmail/v1/users/me/watch' => Http::response([
                'historyId' => 'history-renewed',
                'expiration' => (string) now()->addDays(7)->valueOf(),
            ]),
        ]);

        $this->artisan('gmail:renew-watch')
            ->expectsOutputToContain('support@example.com: watch renewed.')
            ->assertExitCode(0);

        Http::assertSent(fn ($request) => $request->url() === 'https://gmail.googleapis.com/gmail/v1/users/me/watch'
            && $request['topicName'] === 'projects/perpetual/topics/gmail-inbound');

        $account = ConnectedAccount::where('platform', 'gmail')->firstOrFail();

        $this->assertSame('history-renewed', $account->provider_meta['watch_history_id']);
        $this->assertDatabaseHas('automation_logs', [
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'event_type' => 'gmail_watch_renewed',
            'status' => 'success',
        ]);
    }

    public function test_gmail_renew_watch_command_skips_watches_that_are_still_valid(): void
    {
        config(['services.gmail.pubsub_topic' => 'projects/perpetual/topics/gmail-inbound']);

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $this->createGmailAccount($business, [
            'provider_meta' => [
                'email' => 'support@example.com',
                'watch_expiration' => (string) now()->addDays(3)->valueOf(),
            ],
        ]);

        Http::fake();

        $this->artisan('gmail:renew-watch')
            ->expectsOutputToContain('support@example.com: watch still valid until')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_staff_reply_to_gmail_sends_reply_through_gmail_api(): void
    {
        Http::fake([
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'id' => 'sent-gmail-1',
                'threadId' => 'thread-reply',
                'labelIds' => ['SENT'],
            ]),
        ]);

        $user = User::factory()->create(['name' => 'Demo Owner']);
        $business = $this->createBusiness($user);
        $account = $this->createGmailAccount($business);
        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_name' => 'Ada Customer',
            'customer_external_id' => 'ada@example.com',
            'channel' => 'Gmail',
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Can you send pricing?',
            'metadata' => [
                'source' => 'gmail',
                'gmail_message_id' => 'msg-reply',
                'gmail_thread_id' => 'thread-reply',
                'subject' => 'Pricing',
                'from_email' => 'ada@example.com',
                'to_email' => 'support@example.com',
                'rfc_message_id' => '<msg-reply@example.com>',
                'references' => '<root@example.com>',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.inbox.reply', $conversation), ['body' => 'Pricing starts from NGN 55,000.'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Reply sent.');

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send') {
                return false;
            }

            $encodedRaw = strtr($request['raw'], '-_', '+/');
            $encodedRaw .= str_repeat('=', (4 - strlen($encodedRaw) % 4) % 4);
            $raw = base64_decode($encodedRaw, true);

            return $request['threadId'] === 'thread-reply'
                && str_contains($raw, 'To: ada@example.com')
                && str_contains($raw, 'Subject: Re: Pricing')
                && str_contains($raw, 'In-Reply-To: <msg-reply@example.com>')
                && str_contains($raw, 'Pricing starts from NGN 55,000.');
        });

        $this->assertDatabaseHas('automation_logs', [
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'event_type' => 'manual_reply_saved',
            'status' => 'success',
        ]);
    }

    public function test_account_ui_does_not_expose_gmail_tokens(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $this->createGmailAccount($business, [
            'access_token' => 'secret-access-token',
            'refresh_token' => 'secret-refresh-token',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.accounts'));

        $response->assertOk();
        $response->assertSee('support@example.com');
        $response->assertDontSee('secret-access-token');
        $response->assertDontSee('secret-refresh-token');
    }

    private function createBusiness(User $owner, string $name = 'Lagos Detailing'): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => str($name)->slug().'-gmail-test',
            'category' => 'Auto care',
            'email' => str($name)->slug().'@example.test',
        ]);

        $business->users()->attach($owner->id, ['role' => 'Owner']);

        return $business;
    }

    private function createGmailAccount(Business $business, array $overrides = []): ConnectedAccount
    {
        return ConnectedAccount::create(array_merge([
            'business_id' => $business->id,
            'platform' => 'gmail',
            'account_name' => 'support@example.com',
            'external_account_id' => 'support@example.com',
            'access_token' => 'gmail-access-token',
            'refresh_token' => 'gmail-refresh-token',
            'status' => 'connected',
            'connected_at' => now(),
            'provider_meta' => ['email' => 'support@example.com'],
        ], $overrides));
    }

    private function fakeGmailSync(string $messageId, string $threadId, string $from, string $subject, string $body, array $extraHeaders = []): void
    {
        Http::fake([
            'https://gmail.googleapis.com/gmail/v1/users/me/messages?*' => Http::response([
                'messages' => [['id' => $messageId, 'threadId' => $threadId]],
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/'.$messageId.'*' => Http::response([
                'id' => $messageId,
                'threadId' => $threadId,
                'internalDate' => (string) now()->valueOf(),
                'payload' => [
                    'headers' => [
                        ['name' => 'From', 'value' => $from],
                        ['name' => 'To', 'value' => 'support@example.com'],
                        ['name' => 'Subject', 'value' => $subject],
                        ...$extraHeaders,
                    ],
                    'mimeType' => 'text/plain',
                    'body' => ['data' => $this->base64Url($body)],
                ],
            ]),
        ]);
    }

    private function fakeGmailSyncWithAttachments(string $messageId, string $threadId, array $attachments): void
    {
        $attachmentParts = array_map(fn (array $attachment) => [
            'filename' => $attachment['filename'],
            'mimeType' => $attachment['mime_type'],
            'body' => [
                'attachmentId' => $attachment['attachment_id'],
                'size' => strlen($attachment['contents']),
            ],
        ], $attachments);

        $responses = [
            'https://gmail.googleapis.com/gmail/v1/users/me/messages?*' => Http::response([
                'messages' => [['id' => $messageId, 'threadId' => $threadId]],
            ]),
        ];

        foreach ($attachments as $attachment) {
            if (str_starts_with($attachment['mime_type'], 'audio/') || str_starts_with($attachment['mime_type'], 'video/')) {
                continue;
            }

            $responses['https://gmail.googleapis.com/gmail/v1/users/me/messages/'.$messageId.'/attachments/'.$attachment['attachment_id']] = Http::response([
                'data' => $this->base64Url($attachment['contents']),
            ]);
        }

        $responses['https://gmail.googleapis.com/gmail/v1/users/me/messages/'.$messageId.'*'] = Http::response([
                'id' => $messageId,
                'threadId' => $threadId,
                'internalDate' => (string) now()->valueOf(),
                'payload' => [
                    'headers' => [
                        ['name' => 'From', 'value' => 'Ada Customer <ada@example.com>'],
                        ['name' => 'To', 'value' => 'support@example.com'],
                        ['name' => 'Subject', 'value' => 'Files attached'],
                    ],
                    'mimeType' => 'multipart/mixed',
                    'parts' => [
                        [
                            'mimeType' => 'text/plain',
                            'body' => ['data' => $this->base64Url('Please see attached.')],
                        ],
                        ...$attachmentParts,
                    ],
                ],
            ]);

        Http::fake($responses);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
