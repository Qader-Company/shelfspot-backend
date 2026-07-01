<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

abstract class AbstractTaskActionRule
{
    public static function validate(Task $task, Worker $worker = null): void
    {
        if (!$task) {
            throw new ModelNotFoundException(__('api.not_found'));
        }
    }

    public static function insureTaskIsCharged(Task $task, ?string $message = null): void
    {
        if ($task->payment_status !== TaskPaymentStatusEnum::CHARGED) {
            throw ValidationException::withMessages(['task' => $message ?? __('tasks.validation.accept_charged_only')]);
        }
    }

    public static function insureTaskStatusIsOneOf(Task $task, array $statuses, string $message): void
    {
        if (! in_array($task->status, $statuses, true)) {
            throw ValidationException::withMessages([
                'task' => $message,
            ]);
        }
    }

    public static function insureTaskStatusIsNot(Task $task, array $statuses, string $message): void
    {
        if (in_array($task->status, $statuses, true)) {
            throw ValidationException::withMessages([
                'task' => $message,
            ]);
        }
    }

    public static function insureTaskIsNotDeleted(Task $task): void
    {
        if ($task->company_deleted_at !== null) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.accept_deleted_task')]);
        }
    }

    public static function insureTaskDateIsSameDay(Task $task): void
    {
        if (!$task->date->isSameDay(now())) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.accept_execution_date_only')]);
        }
    }

    public static function insureTaskAssignmentToWorker(Task $task, int $workerId = null, string $message): void
    {
        if ($task->assigned_worker_id !== $workerId) {
            throw ValidationException::withMessages(['task' => $message]);
        }
    }

}
