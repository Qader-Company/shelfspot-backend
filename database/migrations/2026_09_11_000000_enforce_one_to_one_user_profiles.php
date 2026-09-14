<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shelf_spot_admins', function (Blueprint $table) {
            $table->unique('user_id', 'shelf_spot_admins_user_id_unique');
        });

        Schema::table('company_users', function (Blueprint $table) {
            $table->unique('user_id', 'company_users_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropUnique('company_users_user_id_unique');
        });

        Schema::table('shelf_spot_admins', function (Blueprint $table) {
            $table->dropUnique('shelf_spot_admins_user_id_unique');
        });
    }
};
