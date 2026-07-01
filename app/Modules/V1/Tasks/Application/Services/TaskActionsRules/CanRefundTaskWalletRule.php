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

        if ($task->company_id === null) {
            throw ValidationException::withMessages([
                'task' => __('company.wallet.tasks.company_required'),
            ]);
        }

        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::REFUND_REQUESTED, TaskStatusEnum::REJECTED],
            message: __('tasks.validation.refund_refund_requested_or_rejected_only')
        );
        parent::insureTaskIsCharged($task, __('company.wallet.tasks.not_charged'));
    }
}
