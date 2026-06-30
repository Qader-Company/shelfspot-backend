<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Validation\ValidationException;

class CanStartTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, Worker $worker = null): void
    {
        parent::validate($task);

        parent::insureTaskIsCharged($task);
        parent::insureTaskIsNotDeleted($task);
        parent::insureTaskDateIsSameDay($task);
        parent::insureTaskAssignmentToWorker(
            task: $task,
            message: __('tasks.validation.accept_unassigned_only')
        );
        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::PENDING],
            message: __('tasks.validation.accept_pending_only')
        );

    }
}
