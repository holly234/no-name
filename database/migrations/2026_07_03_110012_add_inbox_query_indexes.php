<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['business_id', 'status', 'channel', 'last_message_at'], 'conversations_inbox_state_channel_index');
            $table->index(['business_id', 'channel', 'last_message_at'], 'conversations_inbox_channel_index');
            $table->index(['business_id', 'last_message_at'], 'conversations_inbox_recent_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at'], 'messages_conversation_history_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_history_index');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_inbox_recent_index');
            $table->dropIndex('conversations_inbox_channel_index');
            $table->dropIndex('conversations_inbox_state_channel_index');
        });
    }
};
