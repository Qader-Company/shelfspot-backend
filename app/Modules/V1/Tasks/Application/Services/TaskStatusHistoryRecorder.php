<?php

namespace App\Modules\V1\Tasks\Application\Services;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskStatusHistory;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;

class TaskStatusHistoryRecorder
{
    public function record(
        Task $task,
        ?TaskStatusEnum $fromStatus,
        TaskStatusEnum $toStatus,
        ?User $actor = null,
        array $meta = []
    ): TaskStatusHistory {
        return TaskStatusHistory::query()->create([
            'task_id' => $task->id,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
            'changed_by' => $actor?->id,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
