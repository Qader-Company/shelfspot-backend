<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Application\Services\TaskWorkerAssignmentManager;
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
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder,
        private readonly TaskWorkerAssignmentManager $assignmentManager,
    ) {}

    public function execute(?int $limit = null): int
    {
        $query = Task::query()
            ->where('status', TaskStatusEnum::REOPENED->value)
            ->whereNotNull('reopen_deadline_at')
            ->where('reopen_deadline_at', '<=', now())
            ->orderBy('reopen_deadline_at')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $failed = 0;

        $query->get(['id'])->each(function (Task $task) use (&$failed) {
            DB::transaction(function () use ($task, &$failed) {
                $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

                if ($lockedTask->status !== TaskStatusEnum::REOPENED
                    || $lockedTask->reopen_deadline_at === null
                    || now()->lessThan($lockedTask->reopen_deadline_at)) {
                    return;
                }

                $fromStatus = $lockedTask->status;
                $deadline = $lockedTask->reopen_deadline_at;

                $lockedTask->forceFill([
                    'status' => TaskStatusEnum::FAILED,
                    'failure_reason' => TaskFailureReasonEnum::REOPEN_DEADLINE_EXPIRED,
                    'assigned_worker_id' => null,
                ])->save();

                $this->assignmentManager->closeCurrent(
                    $lockedTask,
                    TaskWorkerAssignmentOutcomeEnum::REOPEN_DEADLINE_EXPIRED,
                );

                $this->statusHistoryRecorder->record(
                    task: $lockedTask,
                    fromStatus: $fromStatus,
                    toStatus: TaskStatusEnum::FAILED,
                    meta: [
                        'failure_reason' => TaskFailureReasonEnum::REOPEN_DEADLINE_EXPIRED->value,
                        'reopen_deadline_at' => $deadline->toDateTimeString(),
                    ],
                );

                $failed++;
            });
        });

        return $failed;
    }
}
