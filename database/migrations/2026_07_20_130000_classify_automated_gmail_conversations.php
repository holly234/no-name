<?php

use App\Models\Conversation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('conversations')
            ->where('channel', 'Gmail')
            ->where('status', Conversation::STATE_NEEDS_HUMAN)
            ->orderBy('id')
            ->chunkById(200, function ($conversations) {
                foreach ($conversations as $conversation) {
                    $latest = DB::table('messages')
                        ->where('conversation_id', $conversation->id)
                        ->latest('created_at')
                        ->latest('id')
                        ->first(['metadata']);

                    $metadata = json_decode((string) ($latest?->metadata ?? '{}'), true) ?: [];
                    $from = strtolower((string) ($metadata['from_email'] ?? $conversation->customer_external_id));
                    $labels = $metadata['label_ids'] ?? [];
                    $noReply = (bool) ($metadata['reply_disabled'] ?? false)
                        || preg_match('/(^|[._+-])(no-?reply|do-?not-?reply|donotreply)([._+-]|@)/i', $from);
                    $automatedCategory = count(array_intersect((array) $labels, [
                        'CATEGORY_PROMOTIONS',
                        'CATEGORY_SOCIAL',
                        'CATEGORY_UPDATES',
                        'CATEGORY_FORUMS',
                    ])) > 0;

                    if ($noReply || $automatedCategory) {
                        DB::table('conversations')->where('id', $conversation->id)->update([
                            'status' => Conversation::STATE_INFORMATIONAL,
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('conversations')
            ->where('channel', 'Gmail')
            ->where('status', Conversation::STATE_INFORMATIONAL)
            ->update(['status' => Conversation::STATE_NEEDS_HUMAN, 'updated_at' => now()]);
    }
};
