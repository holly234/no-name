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
        Schema::create('team_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default('agent');
            $table->string('token')->unique();
            $table->string('status')->default('pending');
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_invites');
    }
};
