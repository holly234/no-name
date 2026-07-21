<?php

namespace Tests\Unit;

use App\Support\AiReplyFormatter;
use PHPUnit\Framework\TestCase;

class AiReplyFormatterTest extends TestCase
{
    public function test_it_turns_an_explicit_chat_break_into_two_messages(): void
    {
        $this->assertSame(
            ['Yes, we can help with that.', 'What day works for you?'],
            AiReplyFormatter::segments('Yes, we can help with that.|||What day works for you?', 'Telegram')
        );
    }

    public function test_it_never_returns_more_than_two_chat_messages(): void
    {
        $this->assertSame(['One.', 'Two.'], AiReplyFormatter::segments('One.|||Two.|||Three.', 'WhatsApp'));
    }

    public function test_it_keeps_email_as_one_message(): void
    {
        $this->assertSame(
            ['First paragraph.|||Second paragraph.'],
            AiReplyFormatter::segments('First paragraph.|||Second paragraph.', 'Gmail')
        );
    }

    public function test_it_splits_an_overlong_chat_reply_at_a_sentence_boundary(): void
    {
        $reply = str_repeat('This is useful context that the customer requested. ', 6)
            .'What time would work best for you tomorrow?';

        $segments = AiReplyFormatter::segments($reply, 'Instagram');

        $this->assertCount(2, $segments);
        $this->assertStringEndsWith('.', $segments[0]);
        $this->assertStringEndsWith('?', $segments[1]);
    }
}
