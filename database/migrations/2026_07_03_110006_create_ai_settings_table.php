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
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('assistant_name')->default('Perpetual Assistant');
            $table->string('tone')->default('professional');
            $table->boolean('auto_reply_enabled')->default(false);
            $table->boolean('human_takeover_enabled')->default(true);
            $table->boolean('business_hours_enabled')->default(false);
            $table->text('fallback_response')->nullable();
            $table->text('escalation_instructions')->nullable();
            $table->text('never_say')->nullable();
            $table->text('handover_rules')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
