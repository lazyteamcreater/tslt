<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voice_sessions', function (Blueprint $table) {
            $table->boolean('mic_locked')
                ->default(false)
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('voice_sessions', function (Blueprint $table) {
            $table->dropColumn('mic_locked');
        });
    }
};
