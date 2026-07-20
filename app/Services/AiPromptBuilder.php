<?php

namespace App\Services;

use App\Data\AiPrompt;
use App\Models\AiSetting;
use App\Models\Conversation;

class AiPromptBuilder
{
    public function build(Conversation $conversation, string $incomingMessage): AiPrompt
    {
        $conversation->loadMissing('business', 'messages');
        $business = $conversation->business;
        $settings = AiSetting::firstOrCreate(['business_id' => $business->id]);
        $faqs = $business->faqs()->limit(20)->get(['question', 'answer']);
        $products = $business->products()->limit(20)->get(['name', 'description', 'price', 'availability', 'ai_notes']);
        $rules = $business->businessRules()->limit(20)->get(['title', 'content', 'rule_type']);
        $history = $conversation->messages->sortByDesc('id')->take(12)->reverse()->map(fn ($message) => [
            'speaker' => $message->direction === 'incoming' ? 'customer' : $message->sender_type,
            'text' => $message->body,
        ])->values();

        $system = <<<'PROMPT'
You are a customer-support assistant inside a business inbox. Return only the requested JSON structure.
Never invent prices, availability, policies, promises, refunds, discounts, or approvals.
Escalate to a human for complaints, refunds, discounts, custom quotations, manager approval, legal threats, low confidence, missing knowledge, or any business rule requiring approval.
When escalating, set requires_human=true, state="Needs Human", and leave reply empty unless a short acknowledgement is explicitly safe.
For a normal helpful reply, set state="Waiting" because the business will wait for the customer after sending.
Keep replies concise, natural, and suitable for a private direct-message conversation.
PROMPT;

        return new AiPrompt($system, json_encode([
            'business' => [
                'name' => $business->name,
                'category' => $business->category,
                'description' => $business->description,
            ],
            'assistant' => [
                'name' => $settings->assistant_name,
                'tone' => $settings->tone,
                'fallback_response' => $settings->fallback_response,
                'escalation_instructions' => $settings->escalation_instructions,
                'never_say' => $settings->never_say,
                'handover_rules' => $settings->handover_rules,
            ],
            'knowledge' => [
                'faqs' => $faqs,
                'products' => $products,
                'business_rules' => $rules,
            ],
            'recent_conversation' => $history,
            'latest_customer_message' => $incomingMessage,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
