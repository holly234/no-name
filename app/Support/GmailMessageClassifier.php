<?php

namespace App\Support;

class GmailMessageClassifier
{
    public static function classify(
        array $headers,
        string $fromEmail,
        string $subject,
        array $labelIds,
        bool $replyDisabled
    ): array {
        if ($replyDisabled) {
            return ['kind' => 'informational', 'reason' => 'reply-disabled'];
        }

        $automatedLabels = ['CATEGORY_PROMOTIONS', 'CATEGORY_SOCIAL', 'CATEGORY_UPDATES', 'CATEGORY_FORUMS'];
        if (array_intersect($labelIds, $automatedLabels)) {
            return ['kind' => 'informational', 'reason' => 'gmail-category'];
        }

        if (! empty($headers['list-unsubscribe']) || ! empty($headers['list-id'])) {
            return ['kind' => 'informational', 'reason' => 'mailing-list'];
        }

        $precedence = strtolower((string) ($headers['precedence'] ?? ''));
        $autoSubmitted = strtolower((string) ($headers['auto-submitted'] ?? ''));
        if (in_array($precedence, ['bulk', 'list', 'junk'], true)
            || ($autoSubmitted !== '' && $autoSubmitted !== 'no')
            || ! empty($headers['x-auto-response-suppress'])) {
            return ['kind' => 'informational', 'reason' => 'automated-header'];
        }

        $address = strtolower($fromEmail);
        $localPart = strstr($address, '@', true) ?: $address;
        if (preg_match('/(^|[._+-])(notifications?|notify|alerts?|newsletter|news|marketing|updates?|mailer|campaign|digest)([._+-]|$)/i', $localPart)
            || str_ends_with($address, '@facebookmail.com')) {
            return ['kind' => 'informational', 'reason' => 'automated-sender'];
        }

        $normalizedSubject = strtolower(trim($subject));
        if (preg_match('/\b(unsubscribe|newsletter|weekly digest|daily digest|security alert|new notification|welcome to|limited time|special offer|sale ends|% off)\b/i', $normalizedSubject)) {
            return ['kind' => 'informational', 'reason' => 'automated-subject'];
        }

        return ['kind' => 'actionable', 'reason' => 'replyable-sender'];
    }
}
