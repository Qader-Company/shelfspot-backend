<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;

class CanFailExpiredTaskRule extends AbstractTaskActionRule
{
    public static function isExpired(Task $task): bool
    {
        parent::validate($task);

        if ($task->status === TaskStatusEnum::PENDING) {
            return $task->date->isBefore(now()->startOfDay());
        }

        return $task->status === TaskStatusEnum::STARTED
            && $task->start_deadline_at !== null
            && $task->start_deadline_at->isPast();
    }
}
