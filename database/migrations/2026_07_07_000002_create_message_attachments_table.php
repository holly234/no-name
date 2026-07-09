<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_attachment_id');
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('disk')->default('local');
            $table->string('storage_path');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'provider', 'provider_attachment_id'], 'message_attachments_provider_unique');
            $table->index(['business_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
