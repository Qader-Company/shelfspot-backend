<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;

class CanCancelTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, int $workerId = null): void
    {
        parent::validate($task, $workerId);
        parent::insureTaskAssignmentToWorker(
            task: $task,
            workerId: $workerId,
            message: __('tasks.validation.worker_not_assigned')
        );
        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::STARTED, TaskStatusEnum::IN_PROGRESS],
            message: __('tasks.validation.cancel_active_only')
        );

    }
}
