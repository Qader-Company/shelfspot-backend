<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->index(['deleted_at', 'is_active'], 'companies_deleted_active_idx');
        });

        Schema::table('workers', function (Blueprint $table) {
            $table->index(['deleted_at', 'is_active'], 'workers_deleted_active_idx');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['created_at', 'status'], 'tasks_created_status_idx');
            $table->index(['created_at', 'company_id'], 'tasks_created_company_idx');
        });

        Schema::table('company_wallet_transactions', function (Blueprint $table) {
            $table->index(['type', 'created_at', 'company_id'], 'company_wallet_type_created_company_idx');
        });

        Schema::table('task_worker_assignments', function (Blueprint $table) {
            $table->index(['outcome', 'updated_at', 'worker_id'], 'task_worker_outcome_updated_worker_idx');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_deleted_active_idx');
        });

        Schema::table('workers', function (Blueprint $table) {
            $table->dropIndex('workers_deleted_active_idx');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_created_status_idx');
            $table->dropIndex('tasks_created_company_idx');
        });

        Schema::table('company_wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('company_wallet_type_created_company_idx');
        });

        Schema::table('task_worker_assignments', function (Blueprint $table) {
            $table->dropIndex('task_worker_outcome_updated_worker_idx');
        });
    }
};
