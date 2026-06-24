<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Validation\ValidationException;

class CanExecuteTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, int $workerId = null): void
    {
        parent::validate($task, $workerId);
        parent::insureTaskStatusIsOneOf(
            $task,
            [
                TaskStatusEnum::STARTED,
            ],
            __('tasks.validation.start_accepted_only')
        );
        parent::insureTaskAssignmentToWorker(
            $task,
            $workerId,
            __('tasks.validation.worker_not_assigned')
        );

        if ($task->start_deadline_at !== null && now()->greaterThan($task->start_deadline_at)) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.start_deadline_expired')]);
        }
    }
}
