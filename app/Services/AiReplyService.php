<?php

namespace App\Services;

use App\Models\Conversation;

class AiReplyService
{
    public function decideState(string $message, float $confidence = 0.82): string
    {
        $text = strtolower($message);

        $humanTriggers = [
            'discount',
            'complaint',
            'refund',
            'custom quote',
            'quotation',
            'manager',
            'approval',
            'angry',
            'lawsuit',
        ];

        foreach ($humanTriggers as $trigger) {
            if (str_contains($text, $trigger)) {
                return Conversation::STATE_NEEDS_HUMAN;
            }
        }

        if ($confidence < 0.65) {
            return Conversation::STATE_NEEDS_HUMAN;
        }

        if (str_contains($text, 'thank') || str_contains($text, 'resolved')) {
            return Conversation::STATE_CLOSED;
        }

        return Conversation::STATE_AI_HANDLING;
    }

    public function generatePlaceholderReply(array $context = []): string
    {
        $businessName = data_get($context, 'business.name', 'the business');

        return "Thanks for reaching out to {$businessName}. I can help with availability, pricing, booking details, and common questions. A team member will step in if this needs approval.";
    }
}
