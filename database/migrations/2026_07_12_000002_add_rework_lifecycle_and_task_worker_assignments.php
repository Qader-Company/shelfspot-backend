<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('reopen_deadline_at')
                ->nullable()
                ->after('reopened_at');
            $table->string('failure_reason')
                ->nullable()
                ->after('reopen_deadline_at');
            $table->index(['status', 'reopen_deadline_at'], 'tasks_status_reopen_deadline_idx');
        });

        Schema::create('task_worker_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->string('assignment_type');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->string('outcome')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'unassigned_at'], 'task_worker_assignments_task_open_idx');
            $table->index(['worker_id', 'unassigned_at', 'assigned_at'], 'task_worker_assignments_worker_open_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_worker_assignments');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_reopen_deadline_idx');
            $table->dropColumn(['reopen_deadline_at', 'failure_reason']);
        });
    }
};
