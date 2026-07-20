<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_invites', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('status')->index();
        });

        DB::table('team_invites')->where('status', 'pending')->whereNull('expires_at')->update([
            'expires_at' => now()->addDays(7),
        ]);

        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['business_id', 'status', 'ai_mode', 'last_message_at'], 'conversations_ai_recovery_index');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['business_id', 'direction', 'created_at'], 'messages_analytics_index');
        });
        Schema::table('ai_usage_records', function (Blueprint $table) {
            $table->index(['message_id', 'status'], 'ai_usage_message_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage_records', fn (Blueprint $table) => $table->dropIndex('ai_usage_message_status_index'));
        Schema::table('messages', fn (Blueprint $table) => $table->dropIndex('messages_analytics_index'));
        Schema::table('conversations', fn (Blueprint $table) => $table->dropIndex('conversations_ai_recovery_index'));
        Schema::table('team_invites', fn (Blueprint $table) => $table->dropColumn('expires_at'));
    }
};
