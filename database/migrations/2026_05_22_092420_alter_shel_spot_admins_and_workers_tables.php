<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shel_spot_admins', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('user_id');
        });

        DB::statement('ALTER TABLE shel_spot_admins MODIFY user_id BIGINT UNSIGNED');

        Schema::table('shel_spot_admins', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('workers', function (Blueprint $table) {
            $table->decimal('wallet_balance', 10, 2)->default(0)->after('is_active');
            $table->decimal('last_latitude', 10, 7)->nullable()->after('wallet_balance');
            $table->decimal('last_longitude', 10, 7)->nullable()->after('last_latitude');
            $table->timestamp('location_updated_at')->nullable()->after('last_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn(['wallet_balance', 'last_latitude', 'last_longitude', 'location_updated_at']);
        });

        Schema::table('shel_spot_admins', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('is_active');
        });
    }
};
