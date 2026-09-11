<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')
                ->nullable()
                ->after('email');

            $table->string('phone', 30)
                ->nullable()
                ->after('avatar_url');

            $table->string('gender', 20)
                ->nullable()
                ->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_url',
                'phone',
                'gender',
            ]);
        });
    }
};
