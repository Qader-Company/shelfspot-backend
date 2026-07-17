<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'price')) {
            Schema::table('services', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->default(0)->after('key');
            });
        }

        if (Schema::hasColumn('services', 'minimum_price')) {
            DB::table('services')->update(['price' => DB::raw('minimum_price')]);

            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('minimum_price');
            });
        }

        if (Schema::hasColumn('services', 'minimum_execution_time')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('minimum_execution_time');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('services', 'minimum_price')) {
            Schema::table('services', function (Blueprint $table) {
                $table->decimal('minimum_price', 10, 2)->default(0)->after('key');
            });

            DB::table('services')->update(['minimum_price' => DB::raw('price')]);
        }

        if (! Schema::hasColumn('services', 'minimum_execution_time')) {
            Schema::table('services', function (Blueprint $table) {
                $table->smallInteger('minimum_execution_time')->default(0)->after('minimum_price');
            });
        }
    }
};
