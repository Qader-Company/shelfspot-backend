<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
        });

        // Preserve the previously stored single-language description as English.
        DB::table('platform_settings')
            ->whereNull('description_en')
            ->update(['description_en' => DB::raw('description')]);
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['description_ar', 'description_en']);
        });
    }
};
