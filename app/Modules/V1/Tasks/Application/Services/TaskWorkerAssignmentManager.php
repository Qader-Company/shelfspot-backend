<?php

namespace App\Modules\V1\Tasks\Application\Services;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskWorkerAssignment;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentOutcomeEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;

class TaskWorkerAssignmentManager
{
    public function assign(
        Task $task,
        Worker $worker,
        TaskWorkerAssignmentTypeEnum $type,
        ?User $assignedBy = null,
    ): TaskWorkerAssignment {
        return TaskWorkerAssignment::query()->create([
            'task_id' => $task->id,
            'worker_id' => $worker->id,
            'assignment_type' => $type,
            'assigned_by' => $assignedBy?->id,
            'assigned_at' => now(),
        ]);
    }

    public function closeCurrent(
        Task $task,
        TaskWorkerAssignmentOutcomeEnum $outcome,
        ?string $reason = null,
    ): ?TaskWorkerAssignment {
        $assignment = TaskWorkerAssignment::query()
            ->where('task_id', $task->id)
            ->whereNull('unassigned_at')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if ($assignment === null) {
            return null;
        }

        $assignment->forceFill([
            'unassigned_at' => now(),
            'outcome' => $outcome,
            'reason' => $reason,
        ])->save();

        return $assignment;
    }
}
