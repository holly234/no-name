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
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connected_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->string('status');
            $table->text('message');
            $table->text('error_details')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
