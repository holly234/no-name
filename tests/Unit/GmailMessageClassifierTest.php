<?php

namespace Tests\Unit;

use App\Support\GmailMessageClassifier;
use PHPUnit\Framework\TestCase;

class GmailMessageClassifierTest extends TestCase
{
    public function test_it_recognizes_common_automated_mail_signals(): void
    {
        $cases = [
            [['list-unsubscribe' => '<https://example.test/unsubscribe>'], 'hello@example.test', 'Hello', [], false],
            [[], 'notifications@facebookmail.com', 'You have an update', [], false],
            [[], 'team@example.test', 'Weekly digest', [], false],
            [[], 'person@example.test', 'Hello', ['CATEGORY_PROMOTIONS'], false],
        ];

        foreach ($cases as [$headers, $from, $subject, $labels, $replyDisabled]) {
            $this->assertSame('informational', GmailMessageClassifier::classify(
                $headers, $from, $subject, $labels, $replyDisabled
            )['kind']);
        }
    }

    public function test_it_keeps_a_normal_customer_email_actionable(): void
    {
        $result = GmailMessageClassifier::classify(
            [], 'ada@example.com', 'Please send your price', ['INBOX'], false
        );

        $this->assertSame('actionable', $result['kind']);
    }
}
