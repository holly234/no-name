<?php

use App\Models\Conversation;
use App\Support\GmailMessageClassifier;
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
                    $classification = GmailMessageClassifier::classify(
                        [],
                        (string) ($metadata['from_email'] ?? $conversation->customer_external_id),
                        (string) ($metadata['subject'] ?? ''),
                        (array) ($metadata['label_ids'] ?? []),
                        (bool) ($metadata['reply_disabled'] ?? false)
                    );

                    if ($classification['kind'] === 'informational') {
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
        // Classification corrections are intentionally not reversed.
    }
};
