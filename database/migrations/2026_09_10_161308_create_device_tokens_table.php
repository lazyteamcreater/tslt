<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('token', 512)
                ->unique();

            $table->string('platform', 20)
                ->nullable()
                ->index();

            $table->string('device_name', 100)
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamp('last_used_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
