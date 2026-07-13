<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskWorkerAssignmentManager;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentOutcomeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyRejectTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskWorkerAssignmentManager $assignmentManager,
    ) {}

    public function execute(Task $task, User $actor, string $reason): Task
    {
        return DB::transaction(function () use ($task, $actor, $reason) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

            if ($lockedTask->status !== TaskStatusEnum::COMPLETED) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.reject_completed_only')]);
            }

            if ($lockedTask->auto_accept_at !== null && now()->greaterThanOrEqualTo($lockedTask->auto_accept_at)) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.reject_review_window_expired')]);
            }

            $fromStatus = $lockedTask->status;
            $lockedTask->forceFill([
                'status' => TaskStatusEnum::REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->assignmentManager->closeCurrent(
                $lockedTask,
                TaskWorkerAssignmentOutcomeEnum::REJECTED,
                $reason,
            );

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::REJECTED,
                $actor,
                ['actor_type' => 'company', 'reason' => $reason]
            );

            return $lockedTask->refresh();
        });
    }
}
