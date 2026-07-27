<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Validation\ValidationException;

class CanRejectTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, ?Worker $worker = null): void
    {
        parent::validate($task);

        parent::insureTaskStatusIsOneOf(
            task: $task,
            statuses: [TaskStatusEnum::COMPLETED],
            message: __('tasks.validation.reject_completed_only'),
        );

        if ($task->auto_accept_at !== null && now()->greaterThanOrEqualTo($task->auto_accept_at)) {
            throw ValidationException::withMessages([
                'task' => __('tasks.validation.reject_review_window_expired'),
            ]);
        }
    }
}
