<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Validation\ValidationException;

class CanRefundTaskWalletRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, Worker $worker = null): void
    {
        parent::validate($task);

        parent::insureTaskStatusIsOneOf(
            $task->status,
            [TaskStatusEnum::REFUND_REQUESTED, TaskStatusEnum::REJECTED],
            __('tasks.validation.refund_refund_requested_or_rejected_only'),
        );

        parent::insureTaskIsCharged($task);

        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::REFUND_REQUESTED, TaskStatusEnum::REJECTED],
            message: __('tasks.validation.refund_refund_requested_or_rejected_only')
        );
        parent::insureTaskIsCharged($task, __('company.wallet.tasks.not_charged'));
    }
}
