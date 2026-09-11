<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('media_category_id')
                ->nullable()
                ->constrained('media_categories')
                ->nullOnDelete();

            $table->string('type', 20)
                ->index();

            $table->string('title', 255);

            $table->text('description')
                ->nullable();

            $table->string('speaker', 150)
                ->nullable();

            $table->text('source_url');

            $table->string('thumbnail_url')
                ->nullable();

            $table->unsignedInteger('duration_seconds')
                ->nullable();

            $table->unsignedBigInteger('view_count')
                ->default(0);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamp('published_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'type',
                'is_active',
                'published_at',
            ]);

            $table->index([
                'media_category_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
