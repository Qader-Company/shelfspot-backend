<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;

class CanDeleteTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, Worker $worker = null): void
    {
        parent::validate($task, $worker?->id);
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
