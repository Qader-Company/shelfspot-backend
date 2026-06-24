<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Validation\ValidationException;

class CanReassignTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, Worker $worker = null, bool $workerHasInProgressTask = false): void
    {
        parent::validate($task, $worker->id);
        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::WORKER_CANCELLED, TaskStatusEnum::STARTED],
            message: __('tasks.validation.reassign_cancelled_only')
        );

        if (! $worker->is_active) {
            throw ValidationException::withMessages(['worker' => __('tasks.validation.reassign_active_worker_only')]);
        }

        if ($workerHasInProgressTask) {
            throw ValidationException::withMessages(['worker' => __('tasks.validation.reassign_worker_busy')]);
        }
    }
}
