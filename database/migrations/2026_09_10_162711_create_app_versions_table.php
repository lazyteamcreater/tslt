<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();

            $table->string('platform', 20)
                ->index();

            $table->string('latest_version', 30);

            $table->unsignedInteger('latest_build');

            $table->string('minimum_version', 30)
                ->nullable();

            $table->unsignedInteger('minimum_build')
                ->nullable();

            $table->boolean('force_update')
                ->default(false);

            $table->string('download_url')
                ->nullable();

            $table->text('message')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
