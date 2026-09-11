<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('guest_id', 64)
                ->nullable()
                ->index();

            $table->string('guest_name', 100)
                ->nullable();

            $table->foreignId('reply_to_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();

            $table->string('type', 20)
                ->default('text')
                ->index();

            $table->text('body');

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'created_at',
                'id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
