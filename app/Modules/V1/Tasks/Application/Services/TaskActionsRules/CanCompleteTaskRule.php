<?php

namespace App\Modules\V1\Tasks\Application\Services\TaskActionsRules;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Validation\ValidationException;

class CanCompleteTaskRule extends AbstractTaskActionRule
{
    public static function validate(Task $task, Worker $worker = null): void
    {
        parent::validate($task, $worker->id);
        parent::insureTaskStatusIsOneOf(
            $task,
            [
                TaskStatusEnum::IN_PROGRESS,
            ],
            __('tasks.validation.complete_in_progress_only')
        );
        parent::insureTaskAssignmentToWorker(
            $task,
            $worker->id,
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
