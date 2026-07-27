<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;

class CanUpdateTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, ?Worker $worker = null): void
    {
        parent::validate($task);
        parent::insureTaskStatusIsOneOf(
            $task,
            [TaskStatusEnum::DRAFT, TaskStatusEnum::PENDING, TaskStatusEnum::FAILED],
            __('tasks.validation.update_task')
        );
    }
}
