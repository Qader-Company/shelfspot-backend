<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('start_deadline_at')->nullable()->after('accepted_at');
            $table->timestamp('worker_cancelled_at')->nullable()->after('decline_reason');
            $table->text('worker_cancel_reason')->nullable()->after('worker_cancelled_at');
            $table->timestamp('company_deleted_at')->nullable()->after('charged_at');

            $table->index(['status', 'payment_status', 'date'], 'tasks_status_payment_date_idx');
            $table->index(['company_deleted_at', 'company_id'], 'tasks_company_deleted_company_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_payment_date_idx');
            $table->dropIndex('tasks_company_deleted_company_idx');
            $table->dropColumn([
                'start_deadline_at',
                'worker_cancelled_at',
                'worker_cancel_reason',
                'company_deleted_at',
            ]);
        });
    }
};
