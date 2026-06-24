<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Validation\ValidationException;

class CanDeleteTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, int $workerId = null ): void
    {
        parent::validate($task, $workerId);
        parent::insureTaskStatusIsNot(
            $task,
            [
                TaskStatusEnum::IN_PROGRESS,
                TaskStatusEnum::WORKER_CANCELLED,
                TaskStatusEnum::COMPLETED,
            ],
            __('tasks.validation.accept_deleted_task')
        );
    }
}
