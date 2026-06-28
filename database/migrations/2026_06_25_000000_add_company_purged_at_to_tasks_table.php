<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('company_purged_at')->nullable()->after('company_deleted_at');
            $table->index(['company_purged_at', 'company_id'], 'tasks_company_purged_company_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_company_purged_company_idx');
            $table->dropColumn('company_purged_at');
        });
    }
};
