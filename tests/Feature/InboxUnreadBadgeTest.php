<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InboxUnreadBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_badge_only_shows_for_latest_incoming_message(): void
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

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Lagos Detailing Instagram',
            'external_account_id' => 'lagos-detailing-instagram',
            'status' => 'connected',
        ]);

        $incomingCustomer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Unread Customer',
            'external_id' => 'customer-unread',
            'channel' => 'Instagram',
        ]);

        $incomingConversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $incomingCustomer->id,
            'customer_name' => 'Unread Customer',
            'customer_external_id' => 'customer-unread',
            'channel' => 'Instagram',
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $incomingConversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => 'Please help with my booking.',
        ]);

        $repliedCustomer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Replied Customer',
            'external_id' => 'customer-replied',
            'channel' => 'WhatsApp',
        ]);

        $repliedConversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $repliedCustomer->id,
            'customer_name' => 'Replied Customer',
            'customer_external_id' => 'customer-replied',
            'channel' => 'WhatsApp',
            'status' => Conversation::STATE_WAITING,
            'ai_mode' => 'human',
            'last_message_at' => now()->subMinute(),
        ]);

        Message::create([
            'conversation_id' => $repliedConversation->id,
            'business_id' => $business->id,
            'direction' => 'outgoing',
            'sender_type' => 'human',
            'body' => 'Thanks, we will follow up.',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard.inbox'));

        $response->assertOk();
        $response->assertSee('Unread Customer');
        $response->assertSee('Replied Customer');
        $response->assertSee('aria-label="1 unread message"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'aria-label="1 unread message"'));
    }

    public function test_inbox_search_filters_conversations(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Lagos Detailing',
            'slug' => 'lagos-detailing-search-test',
            'category' => 'Auto care',
            'email' => 'lagos-search@example.test',
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Lagos Detailing Instagram',
            'external_account_id' => 'lagos-detailing-instagram-search',
            'status' => 'connected',
        ]);

        $matchingConversation = $this->createConversationWithMessage(
            business: $business,
            account: $account,
            customerName: 'Kemi Adebayo',
            body: 'I want to file a complaint about yesterday service.',
        );

        $this->createConversationWithMessage(
            business: $business,
            account: $account,
            customerName: 'Daniel Okafor',
            body: 'What does ceramic coating cost?',
        );

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard.inbox', ['q' => 'complaint']));

        $response->assertOk();
        $response->assertSee('Kemi Adebayo');
        $response->assertDontSee('Daniel Okafor');
        $response->assertSee('value="complaint"', false);
        $response->assertSee('conversation='.$matchingConversation->id, false);
    }

    public function test_inbox_search_matches_gmail_subject_and_attachment_filename(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Lagos Detailing',
            'slug' => 'lagos-detailing-gmail-search-test',
            'category' => 'Auto care',
            'email' => 'lagos-gmail-search@example.test',
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'gmail',
            'account_name' => 'support@example.com',
            'external_account_id' => 'support@example.com',
            'status' => 'connected',
        ]);

        $invoiceConversation = $this->createConversationWithMessage(
            business: $business,
            account: $account,
            customerName: 'Ada Customer',
            body: 'Please see attached.',
            channel: 'Gmail',
            metadata: ['source' => 'gmail', 'subject' => 'July invoice', 'from_email' => 'ada@example.com'],
        );
        MessageAttachment::create([
            'message_id' => $invoiceConversation->messages()->first()->id,
            'business_id' => $business->id,
            'provider' => 'gmail',
            'provider_attachment_id' => 'gmail-attachment-1',
            'filename' => 'detailing-invoice.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'local',
            'storage_path' => 'test/invoice.pdf',
        ]);

        $this->createConversationWithMessage(
            business: $business,
            account: $account,
            customerName: 'Unrelated Customer',
            body: 'General enquiry',
            channel: 'Gmail',
            metadata: ['source' => 'gmail', 'subject' => 'Hello'],
        );

        $subjectResponse = $this
            ->actingAs($user)
            ->get(route('dashboard.inbox', ['q' => 'July invoice']));

        $subjectResponse->assertOk();
        $subjectResponse->assertSee('Ada Customer');
        $subjectResponse->assertDontSee('Unrelated Customer');

        $attachmentResponse = $this
            ->actingAs($user)
            ->get(route('dashboard.inbox', ['q' => 'detailing-invoice']));

        $attachmentResponse->assertOk();
        $attachmentResponse->assertSee('Ada Customer');
        $attachmentResponse->assertDontSee('Unrelated Customer');
    }

    public function test_inbox_channel_filter_limits_conversations_to_selected_social_platform(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Lagos Detailing',
            'slug' => 'lagos-detailing-channel-test',
            'category' => 'Auto care',
            'email' => 'lagos-channel@example.test',
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        $instagram = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Lagos Detailing Instagram',
            'external_account_id' => 'lagos-detailing-instagram-channel',
            'status' => 'connected',
        ]);

        $whatsapp = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'WhatsApp',
            'account_name' => 'Lagos Detailing WhatsApp',
            'external_account_id' => 'lagos-detailing-whatsapp-channel',
            'status' => 'connected',
        ]);

        $this->createConversationWithMessage(
            business: $business,
            account: $instagram,
            customerName: 'Instagram Customer',
            body: 'Instagram question',
            channel: 'Instagram',
        );

        $this->createConversationWithMessage(
            business: $business,
            account: $whatsapp,
            customerName: 'WhatsApp Customer',
            body: 'WhatsApp question',
            channel: 'WhatsApp',
        );

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard.inbox', ['channel' => 'WhatsApp']));

        $response->assertOk();
        $response->assertSee('WhatsApp Customer');
        $response->assertDontSee('Instagram Customer');
        $response->assertSee('channel=WhatsApp', false);
    }

    public function test_opening_conversation_marks_it_read_for_current_user(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Lagos Detailing',
            'slug' => 'lagos-detailing-read-test',
            'category' => 'Auto care',
            'email' => 'lagos-read@example.test',
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Lagos Detailing Instagram',
            'external_account_id' => 'lagos-detailing-instagram-read',
            'status' => 'connected',
        ]);

        $conversation = $this->createConversationWithMessage(
            business: $business,
            account: $account,
            customerName: 'Kemi Adebayo',
            body: 'Please help with my booking.',
        );

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard.inbox', ['conversation' => $conversation->id]));

        $response->assertOk();
        $response->assertDontSee('aria-label="1 unread message"', false);
        $this->assertDatabaseHas('conversation_reads', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_staff_reply_can_include_manual_attachments(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Lagos Detailing',
            'slug' => 'lagos-detailing-attachment-test',
            'category' => 'Auto care',
            'email' => 'lagos-attachment@example.test',
        ]);
        $business->users()->attach($user->id, ['role' => 'Owner']);

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Instagram',
            'account_name' => 'Lagos Detailing Instagram',
            'external_account_id' => 'lagos-detailing-instagram-attachment',
            'status' => 'connected',
        ]);

        $conversation = $this->createConversationWithMessage(
            business: $business,
            account: $account,
            customerName: 'Kemi Adebayo',
            body: 'Please send the price sheet.',
        );

        $response = $this
            ->actingAs($user)
            ->post(route('dashboard.inbox.reply', $conversation), [
                'body' => '',
                'attachments' => [
                    UploadedFile::fake()->create('price-sheet.jpg', 120, 'image/jpeg'),
                ],
            ]);

        $response->assertRedirect();

        $message = Message::where('conversation_id', $conversation->id)
            ->where('direction', 'outgoing')
            ->firstOrFail();

        $attachment = MessageAttachment::where('message_id', $message->id)->firstOrFail();

        $this->assertSame('price-sheet.jpg', $attachment->filename);
        $this->assertSame('manual', $attachment->provider);
        Storage::disk('local')->assertExists($attachment->storage_path);
    }


    private function createConversationWithMessage(
        Business $business,
        ConnectedAccount $account,
        string $customerName,
        string $body,
        string $channel = 'Instagram',
        array $metadata = [],
    ): Conversation {
        $externalId = str($customerName)->slug().'-customer';

        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => $customerName,
            'external_id' => $externalId,
            'channel' => $channel,
        ]);

        $conversation = Conversation::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'customer_id' => $customer->id,
            'customer_name' => $customerName,
            'customer_external_id' => $externalId,
            'channel' => $channel,
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $business->id,
            'direction' => 'incoming',
            'sender_type' => 'customer',
            'body' => $body,
            'metadata' => $metadata,
        ]);

        return $conversation;
    }
}
