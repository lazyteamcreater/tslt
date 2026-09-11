<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_participant_locks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voice_session_id')
                ->constrained('voice_sessions')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('is_locked')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'voice_session_id',
                'user_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_participant_locks');
    }
};
