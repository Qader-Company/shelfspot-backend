<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
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

        if ($task->payment_status !== TaskPaymentStatusEnum::CHARGED) {
            throw ValidationException::withMessages([
                'task' => __('company.wallet.tasks.not_charged'),
            ]);
        }
    }
}
