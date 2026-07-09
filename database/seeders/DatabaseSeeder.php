<?php

namespace Database\Seeders;

use App\Models\AiSetting;
use App\Models\AutomationLog;
use App\Models\Business;
use App\Models\BusinessRule;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Faq;
use App\Models\Message;
use App\Models\Product;
use App\Models\SavedReply;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'demo@perpetualinbox.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $businesses = [
            [
                'name' => 'VIP Rentals',
                'slug' => 'vip-rentals',
                'category' => 'Luxury rentals',
                'description' => 'Premium car and event rentals in Lagos.',
                'customers' => [
                    ['Ada Johnson', 'Instagram', '@adajohnson', Conversation::STATE_NEEDS_HUMAN, 'Can I get a discount if I book two SUVs this weekend?', 'Discount request detected.'],
                    ['Tunde Bello', 'WhatsApp', '+234 812 167 7111', Conversation::STATE_WAITING, 'Please send the booking requirements.', 'AI replied and is waiting.'],
                    ['Maya Cole', 'Facebook', '@mayacole', Conversation::STATE_AI_HANDLING, 'Do you have a Range Rover available tomorrow?', 'AI is checking the knowledge base.'],
                    ['Ife Morgan', 'Instagram', '@ifemorgan', Conversation::STATE_CLOSED, 'Thanks, that answers my question.', 'Conversation closed.'],
                ],
            ],
            [
                'name' => 'Lagos Detailing',
                'slug' => 'lagos-detailing',
                'category' => 'Auto care',
                'description' => 'Mobile and studio detailing services.',
                'customers' => [
                    ['Kemi Adebayo', 'Instagram', '@kemiadebayo', Conversation::STATE_NEEDS_HUMAN, 'I want to file a complaint about yesterday service.', 'Complaint detected.'],
                    ['Daniel Okafor', 'WhatsApp', '+234 803 456 7788', Conversation::STATE_WAITING, 'What does ceramic coating cost?', 'AI sent pricing information.'],
                    ['Simi James', 'Facebook', '@simijames', Conversation::STATE_AI_HANDLING, 'Can you detail my SUV at home?', 'AI is handling the request.'],
                    ['Bola Sanni', 'Instagram', '@bolasanni', Conversation::STATE_CLOSED, 'Resolved, thank you.', 'Conversation closed.'],
                ],
            ],
        ];

        foreach ($businesses as $businessData) {
            $business = Business::create([
                'owner_id' => $user->id,
                'name' => $businessData['name'],
                'slug' => $businessData['slug'],
                'category' => $businessData['category'],
                'email' => strtolower(str_replace(' ', '', $businessData['name'])).'@example.test',
                'description' => $businessData['description'],
            ]);

            $business->users()->attach($user->id, ['role' => 'Owner']);

            AiSetting::create([
                'business_id' => $business->id,
                'assistant_name' => $business->name.' Assistant',
                'tone' => 'Helpful, direct, professional',
                'auto_reply_enabled' => true,
                'human_takeover_enabled' => true,
                'fallback_response' => 'A team member will review this and respond shortly.',
                'handover_rules' => 'Escalate discounts, complaints, refunds, custom quotes, manager approvals, and low-confidence requests.',
            ]);

            $instagram = ConnectedAccount::create([
                'business_id' => $business->id,
                'platform' => 'Instagram',
                'account_name' => $business->name.' Instagram',
                'external_account_id' => $business->slug.'-instagram',
                'status' => 'connected',
                'connected_at' => now()->subDays(4),
                'access_token' => 'fake-demo-token',
            ]);

            ConnectedAccount::create([
                'business_id' => $business->id,
                'platform' => 'WhatsApp',
                'account_name' => $business->name.' WhatsApp',
                'external_account_id' => $business->slug.'-whatsapp',
                'phone_number_id' => 'demo-phone-'.$business->id,
                'status' => 'connected',
                'connected_at' => now()->subDays(3),
                'access_token' => 'fake-demo-token',
            ]);

            Faq::create([
                'business_id' => $business->id,
                'question' => 'What are your business hours?',
                'answer' => 'We respond every day from 9am to 7pm.',
                'category' => 'Operations',
            ]);

            Faq::create([
                'business_id' => $business->id,
                'question' => 'How do customers book?',
                'answer' => 'Customers share their preferred date, service, location, and phone number.',
                'category' => 'Booking',
            ]);

            Product::create([
                'business_id' => $business->id,
                'name' => $business->name === 'VIP Rentals' ? 'Luxury SUV Weekend Rental' : 'Premium Interior Detail',
                'description' => $business->name === 'VIP Rentals' ? 'Weekend rental package for premium SUVs.' : 'Deep interior cleaning and finishing.',
                'price' => $business->name === 'VIP Rentals' ? 'From NGN 180,000' : 'From NGN 55,000',
                'availability' => 'Available this week',
                'ai_notes' => 'Confirm date and location before quoting final price.',
            ]);

            BusinessRule::create([
                'business_id' => $business->id,
                'rule_type' => 'handover',
                'title' => 'Human approval required',
                'content' => 'Discounts, refunds, complaints, custom quotations, and manager approvals must be escalated.',
            ]);

            SavedReply::create([
                'business_id' => $business->id,
                'title' => 'Pricing follow-up',
                'shortcut' => '/pricing',
                'body' => 'Thanks for reaching out. Please share the service you need, your location, and preferred date so we can confirm pricing and availability.',
            ]);

            foreach ($businessData['customers'] as $index => [$name, $channel, $externalId, $state, $incoming, $note]) {
                $customer = Customer::create([
                    'business_id' => $business->id,
                    'name' => $name,
                    'external_id' => $externalId,
                    'channel' => $channel,
                    'notes' => $note,
                    'tags' => [$state],
                ]);

                $conversation = Conversation::create([
                    'business_id' => $business->id,
                    'connected_account_id' => $instagram->id,
                    'customer_id' => $customer->id,
                    'customer_name' => $name,
                    'customer_external_id' => $customer->external_id,
                    'channel' => $channel,
                    'status' => $state,
                    'ai_mode' => 'auto',
                    'last_message_at' => now()->subMinutes(20 + $index * 18),
                ]);

                Message::create([
                    'conversation_id' => $conversation->id,
                    'business_id' => $business->id,
                    'direction' => 'incoming',
                    'sender_type' => 'customer',
                    'body' => $incoming,
                    'created_at' => $conversation->last_message_at,
                    'updated_at' => $conversation->last_message_at,
                ]);

                if ($state !== Conversation::STATE_NEEDS_HUMAN) {
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'business_id' => $business->id,
                        'direction' => 'outgoing',
                        'sender_type' => $state === Conversation::STATE_CLOSED ? 'human' : 'ai',
                        'body' => $state === Conversation::STATE_CLOSED
                            ? 'Glad we could help. This conversation has been closed.'
                            : 'Thanks for reaching out. I can help with that and will ask a teammate if approval is needed.',
                        'created_at' => $conversation->last_message_at->copy()->addMinute(),
                        'updated_at' => $conversation->last_message_at->copy()->addMinute(),
                    ]);
                }
            }

            AutomationLog::create([
                'business_id' => $business->id,
                'connected_account_id' => $instagram->id,
                'event_type' => 'demo_workspace_seeded',
                'status' => 'success',
                'message' => 'Demo workspace data was created.',
            ]);
        }
    }
}
