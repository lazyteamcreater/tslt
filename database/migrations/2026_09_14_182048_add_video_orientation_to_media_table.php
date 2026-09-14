<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('video_orientation', 20)
                ->nullable()
                ->after('type')
                ->index();
        });

        /*
         * ရှိပြီးသား video တွေကို landscape သတ်မှတ်ပါမယ်။
         * 9:16 video ကို နောက်မှ portrait ပြောင်းနိုင်ပါတယ်။
         */
        DB::table('media')
            ->where('type', 'video')
            ->update([
                'video_orientation' => 'landscape',
            ]);
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex([
                'video_orientation',
            ]);

            $table->dropColumn('video_orientation');
        });
    }
};
