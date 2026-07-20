<?php

namespace App\Services;

use App\Jobs\ProcessAiReply;
use App\Models\AutomationLog;
use App\Models\Conversation;
use App\Support\QueueDispatch;
use Illuminate\Support\Facades\Cache;

class AiReplyRecoveryService
{
    public function recover(int $limit = 50): int
    {
        $conversations = Conversation::query()
            ->with('latestMessage')
            ->where('status', Conversation::STATE_NEEDS_HUMAN)
            ->where('ai_mode', 'auto')
            ->whereHas('business.aiSetting', fn ($query) => $query->where('auto_reply_enabled', true))
            ->whereHas('latestMessage', fn ($query) => $query
                ->where('direction', 'incoming')
                ->where('created_at', '<=', now()->subMinute()))
            ->oldest('last_message_at')
            ->limit(max(1, min(200, $limit)))
            ->get();

        $queued = 0;

        foreach ($conversations as $conversation) {
            $message = $conversation->latestMessage;

            if (! $message || ! Cache::add('ai-reply-recovery:'.$message->id, true, now()->addMinutes(10))) {
                continue;
            }

            QueueDispatch::dispatch(new ProcessAiReply($message->id, recovery: true));
            $queued++;

            AutomationLog::create([
                'business_id' => $conversation->business_id,
                'connected_account_id' => $conversation->connected_account_id,
                'event_type' => 'ai_reply_recovery_queued',
                'status' => 'success',
                'message' => 'An unanswered customer message was queued for AI recovery.',
                'metadata' => [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                ],
            ]);
        }

        return $queued;
    }
}
