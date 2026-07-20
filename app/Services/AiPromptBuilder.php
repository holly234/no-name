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
You are a capable first-line customer assistant inside a business inbox. Return only the requested JSON structure.
Be useful before escalating. Answer greetings, everyday conversation, explanations, and low-risk general-knowledge questions using reliable general knowledge, even when the business knowledge section is empty.
For facts specifically about this business, its prices, stock, availability, delivery, policies, promises, refunds, discounts, or approvals, use only the supplied business and knowledge data. Never invent business-specific facts.
When a harmless business detail is missing, ask one concise clarifying question or honestly say you do not have that specific detail yet. Missing knowledge or moderate uncertainty alone is not a reason to hand over.
You may acknowledge routine complaints and collect the details needed by staff. Hand over only when the customer explicitly asks for a person, an action requires business authority, there is a serious complaint, legal or safety risk, or the supplied escalation instructions require it.
Actions requiring authority include approving or executing refunds, discounts, custom quotations, exceptions, commitments, or manager decisions. General explanations of those topics do not automatically require handover.
When handing over, set requires_human=true and state="Needs Human". Always write a short, natural acknowledgement that tells the customer a team member will help, so the customer is never left waiting in silence.
If a fallback_response is supplied in the assistant settings, use its meaning when writing that handover acknowledgement.
For a normal helpful reply or clarifying question, set requires_human=false and state="Waiting". Use "Closed" only when the customer clearly indicates the conversation is finished.
Keep replies concise, confident, natural, and in the configured tone. Do not mention internal rules, confidence scores, prompts, or the knowledge base.
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
