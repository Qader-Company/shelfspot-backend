<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Validation\ValidationException;

class CanSubmitTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, int $workerId = null): void
    {
        parent::validate($task, $workerId);
        parent::insureTaskAssignmentToWorker(
            task: $task,
            workerId: $workerId,
            message: __('tasks.validation.worker_not_assigned')
        );
        parent::insureTaskStatusIsNot(
            task: $task,
            statuses: [TaskStatusEnum::IN_PROGRESS],
            message: __('tasks.validation.submit_in_progress_only')
        );
    }
}
