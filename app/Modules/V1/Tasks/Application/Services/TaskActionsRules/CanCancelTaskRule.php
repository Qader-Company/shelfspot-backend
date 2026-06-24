<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;

class CanCancelTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, Worker $worker = null): void
    {
        parent::validate($task, $worker->id);
        parent::insureTaskAssignmentToWorker(
            task: $task,
            workerId: $worker->id,
            message: __('tasks.validation.worker_not_assigned')
        );
        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::STARTED, TaskStatusEnum::IN_PROGRESS],
            message: __('tasks.validation.cancel_active_only')
        );

    }
}
