<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_categories', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);

            $table->string('type', 20)
                ->index();

            $table->string('image_url')
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0)
                ->index();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->index([
                'type',
                'is_active',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_categories');
    }
};
