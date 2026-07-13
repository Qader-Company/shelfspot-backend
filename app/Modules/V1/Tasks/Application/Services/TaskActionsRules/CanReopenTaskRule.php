<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Validation\ValidationException;

class CanReopenTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, ?Worker $worker = null, bool $workerHasActiveTask = false): void
    {
        parent::validate($task);

        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::REJECTED],
            message: __('tasks.validation.reopen_rejected_only'),
        );

        if ($worker === null || ! $worker->is_active) {
            throw ValidationException::withMessages([
                'worker' => __('tasks.validation.reassign_active_worker_only'),
            ]);
        }

        if ($workerHasActiveTask) {
            throw ValidationException::withMessages([
                'worker' => __('tasks.validation.reassign_worker_busy'),
            ]);
        }
    }
}
