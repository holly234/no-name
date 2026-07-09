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
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('account_name');
            $table->string('external_account_id');
            $table->string('page_id')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('status')->default('not_connected');
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'platform']);
            $table->index(['platform', 'external_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connected_accounts');
    }
};
