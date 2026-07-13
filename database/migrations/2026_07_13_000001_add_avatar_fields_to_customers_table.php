<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('avatar_disk')->nullable()->after('notes');
            $table->string('avatar_path')->nullable()->after('avatar_disk');
            $table->string('avatar_url')->nullable()->after('avatar_path');
            $table->string('avatar_provider_id')->nullable()->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_disk',
                'avatar_path',
                'avatar_url',
                'avatar_provider_id',
            ]);
        });
    }
};
