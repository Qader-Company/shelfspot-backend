<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use Illuminate\Validation\ValidationException;

class CanChargeTaskWalletRule extends AbstractTaskActionRule
{
    public static function validate(Task $task): void
    {
        parent::validate($task);

        if ($task->company_id === null) {
            throw ValidationException::withMessages([
                'task' => __('company.wallet.tasks.company_required'),
            ]);
        }

        if ($task->payment_status === TaskPaymentStatusEnum::CHARGED) {
            throw ValidationException::withMessages([
                'task' => __('company.wallet.tasks.already_charged'),
            ]);
        }
    }
}
