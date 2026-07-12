<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;

class CanCompanyCancelTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, ?Worker $worker = null): void
    {
        parent::validate($task);

        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::PENDING, TaskStatusEnum::FAILED],
            message: __('tasks.validation.company_cancel_pending_or_failed_only'),
        );

        parent::insureTaskIsCharged($task, __('company.wallet.tasks.not_charged'));
    }
}
