<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskWorkerAssignmentManager;
use App\Modules\V1\Tasks\Application\Support\TaskSchedulerBatch;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskFailureReasonEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentOutcomeEnum;
use Illuminate\Support\Facades\DB;

class FailExpiredReopenedTasksUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskWorkerAssignmentManager $assignmentManager,
    ) {}

    public function execute(?int $limit = null): int
    {
        $query = $this->taskRepository->query()
            ->where('status', TaskStatusEnum::REOPENED->value)
            ->whereNotNull('reopen_deadline_at')
            ->where('reopen_deadline_at', '<=', now())
            ->reorder();

        $failed = 0;

        $query->lazyById(TaskSchedulerBatch::CHUNK_SIZE)
            ->take(TaskSchedulerBatch::limit($limit))
            ->each(function (Task $task) use (&$failed) {
                DB::transaction(function () use ($task, &$failed) {
                    $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

                    if ($lockedTask->status !== TaskStatusEnum::REOPENED
                        || $lockedTask->reopen_deadline_at === null
                        || now()->lessThan($lockedTask->reopen_deadline_at)) {
                        return;
                    }

                    $fromStatus = $lockedTask->status;
                    $deadline = $lockedTask->reopen_deadline_at;
                    $previousWorkerId = $lockedTask->assigned_worker_id;

                    $lockedTask->forceFill([
                        'status' => TaskStatusEnum::FAILED,
                        'failure_reason' => TaskFailureReasonEnum::REOPEN_DEADLINE_EXPIRED,
                        'assigned_worker_id' => null,
                    ])->save();

                    $this->assignmentManager->closeCurrent(
                        $lockedTask,
                        TaskWorkerAssignmentOutcomeEnum::REOPEN_DEADLINE_EXPIRED,
                    );

                    TaskStatusUpdated::dispatch(
                        $lockedTask,
                        $fromStatus,
                        TaskStatusEnum::FAILED,
                        null,
                        [
                            'failure_reason' => TaskFailureReasonEnum::REOPEN_DEADLINE_EXPIRED->value,
                            'reopen_deadline_at' => $deadline->toDateTimeString(),
                            'previous_worker_id' => $previousWorkerId,
                        ],
                    );

                    $failed++;
                });
            });

        return $failed;
    }
}
