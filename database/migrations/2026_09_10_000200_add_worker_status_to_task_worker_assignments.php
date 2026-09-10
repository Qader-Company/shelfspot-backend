<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_worker_assignments', function (Blueprint $table) {
            $table->string('worker_status')
                ->nullable()
                ->after('assignment_type');
        });

        DB::table('task_worker_assignments')
            ->select(['id', 'task_id', 'unassigned_at', 'outcome'])
            ->orderBy('id')
            ->chunkById(500, function ($assignments): void {
                $taskStatuses = DB::table('tasks')
                    ->whereIn('id', $assignments->pluck('task_id')->unique()->values())
                    ->pluck('status', 'id');

                foreach ($assignments as $assignment) {
                    $taskStatus = (string) ($taskStatuses[$assignment->task_id] ?? 'failed');

                    DB::table('task_worker_assignments')
                        ->where('id', $assignment->id)
                        ->update([
                            'worker_status' => $this->workerStatusFor(
                                taskStatus: $taskStatus,
                                unassignedAt: $assignment->unassigned_at,
                                outcome: $assignment->outcome,
                            ),
                        ]);
                }
            });

        Schema::table('task_worker_assignments', function (Blueprint $table) {
            $table->index(
                ['worker_id', 'worker_status', 'assigned_at'],
                'task_worker_assignments_worker_status_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('task_worker_assignments', function (Blueprint $table) {
            $table->dropIndex('task_worker_assignments_worker_status_idx');
            $table->dropColumn('worker_status');
        });
    }

    private function workerStatusFor(string $taskStatus, mixed $unassignedAt, mixed $outcome): string
    {
        if ($unassignedAt === null) {
            return $taskStatus;
        }

        return match ((string) $outcome) {
            'completed' => $taskStatus === 'accepted' ? 'accepted' : 'completed',
            'rejected' => $taskStatus === 'accepted' ? 'accepted' : 'rejected',
            'worker_cancelled' => 'worker_cancelled',
            'reassigned' => 'reassigned',
            'start_deadline_expired', 'reopen_deadline_expired' => 'failed',
            default => $taskStatus,
        };
    }
};
