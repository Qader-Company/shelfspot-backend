<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Validation\ValidationException;

class CanCompleteTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, int $workerId = null): void
    {
        parent::validate($task, $workerId);
        parent::insureTaskStatusIsOneOf(
            $task,
            [
                TaskStatusEnum::IN_PROGRESS,
            ],
            __('tasks.validation.complete_in_progress_only')
        );
        parent::insureTaskAssignmentToWorker(
            $task,
            $workerId,
            __('tasks.validation.worker_not_assigned')
        );
        self::checkTaskService($task);
    }

    public static function checkTaskService($task)
    {
        if ($task->services->isEmpty()) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.complete_requires_services')]);
        }

        $hasIncompleteServices = $task->services
            ->contains(fn ($service) => $service->status !== TaskServiceStatusEnum::COMPLETED);

        if ($hasIncompleteServices) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.complete_requires_completed_services')]);
        }
    }
}
