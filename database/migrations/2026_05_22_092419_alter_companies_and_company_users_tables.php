<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('timezone')->default('Asia/Riyadh')->after('industry');
            $table->softDeletes();
        });

        Schema::table('company_users', function (Blueprint $table) {
            $table->unique(['company_id', 'user_id'], 'company_users_company_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropUnique('company_users_company_user_unique');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('timezone');
        });
    }
};
