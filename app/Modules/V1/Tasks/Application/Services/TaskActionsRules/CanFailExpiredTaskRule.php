<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;

class CanFailExpiredTaskRule extends AbstractTaskActionRule
{
    public static function isExpired(Task $task): bool
    {
        parent::validate($task);

        if (in_array($task->status, [TaskStatusEnum::PENDING, TaskStatusEnum::WORKER_CANCELLED], true)) {
            return $task->expires_at !== null && $task->expires_at->lessThanOrEqualTo(now());
        }

        return false;
    }
}
