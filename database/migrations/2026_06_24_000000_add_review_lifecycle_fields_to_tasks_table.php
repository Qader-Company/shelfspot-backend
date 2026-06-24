<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('completed_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->timestamp('company_accepted_at')->nullable()->after('rejection_reason');
            $table->timestamp('auto_accept_at')->nullable()->after('company_accepted_at');
            $table->timestamp('auto_accepted_at')->nullable()->after('auto_accept_at');
            $table->timestamp('reopened_at')->nullable()->after('auto_accepted_at');
            $table->text('reopen_reason')->nullable()->after('reopened_at');

            $table->index(['status', 'auto_accept_at'], 'tasks_status_auto_accept_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_auto_accept_idx');
            $table->dropColumn([
                'rejected_at',
                'rejection_reason',
                'company_accepted_at',
                'auto_accept_at',
                'auto_accepted_at',
                'reopened_at',
                'reopen_reason',
            ]);
        });
    }
};
