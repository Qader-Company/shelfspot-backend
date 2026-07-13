<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Validation\ValidationException;

class CanExecuteTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, ?Worker $worker = null): void
    {
        parent::validate($task);
        parent::insureTaskStatusIsOneOf(
            $task,
            [
                TaskStatusEnum::STARTED,
                TaskStatusEnum::REOPENED,
            ],
            __('tasks.validation.start_accepted_only')
        );
        parent::insureTaskAssignmentToWorker(
            $task,
            $worker->id,
            __('tasks.validation.worker_not_assigned')
        );

        if ($task->status === TaskStatusEnum::STARTED
            && $task->start_deadline_at !== null
            && now()->greaterThan($task->start_deadline_at)) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.start_deadline_expired')]);
        }

        if ($task->status === TaskStatusEnum::REOPENED
            && $task->reopen_deadline_at !== null
            && now()->greaterThanOrEqualTo($task->reopen_deadline_at)) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.reopen_deadline_expired')]);
        }
    }
}
