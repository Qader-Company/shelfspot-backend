<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;

class CanSubmitTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, Worker $worker = null): void
    {
        parent::validate($task);
        parent::insureTaskAssignmentToWorker(
            task: $task,
            workerId: $worker->id,
            message: __('tasks.validation.worker_not_assigned')
        );
        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::IN_PROGRESS],
            message: __('tasks.validation.submit_in_progress_only')
        );
    }
}
