<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('host_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('room_name')->unique();

            $table->string('title')->nullable();

            $table->string('status', 20)
                ->default('active')
                ->index();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_sessions');
    }
};
