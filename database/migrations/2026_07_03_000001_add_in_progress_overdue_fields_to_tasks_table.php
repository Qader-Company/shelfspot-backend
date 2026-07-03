<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('expected_completion_at')
                ->nullable()
                ->after('started_at');
            $table->timestamp('in_progress_overdue_at')
                ->nullable()
                ->after('expected_completion_at');
            $table->index(
                ['status', 'in_progress_overdue_at', 'expected_completion_at'],
                'tasks_status_overdue_expected_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_overdue_expected_idx');
            $table->dropColumn(['expected_completion_at', 'in_progress_overdue_at']);
        });
    }
};
