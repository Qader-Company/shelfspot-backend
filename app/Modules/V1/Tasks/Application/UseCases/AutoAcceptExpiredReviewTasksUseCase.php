<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskWorkerAssignmentManager;
use App\Modules\V1\Tasks\Application\Support\TaskSchedulerBatch;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentOutcomeEnum;
use Illuminate\Support\Facades\DB;

class AutoAcceptExpiredReviewTasksUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskWorkerAssignmentManager $assignmentManager,
    ) {}

    public function execute(?int $limit = null): int
    {
        $query = $this->taskRepository->query()
            ->where('status', TaskStatusEnum::COMPLETED->value)
            ->whereNotNull('auto_accept_at')
            ->where('auto_accept_at', '<=', now())
            ->reorder();

        $accepted = 0;

        $query->lazyById(TaskSchedulerBatch::CHUNK_SIZE)
            ->take(TaskSchedulerBatch::limit($limit))
            ->each(function (Task $task) use (&$accepted) {
                DB::transaction(function () use ($task, &$accepted) {
                    $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

                    if ($lockedTask->status !== TaskStatusEnum::COMPLETED
                        || $lockedTask->auto_accept_at === null
                        || now()->lessThan($lockedTask->auto_accept_at)) {
                        return;
                    }

                    $fromStatus = $lockedTask->status;
                    $now = now();

                    $lockedTask->forceFill([
                        'status' => TaskStatusEnum::ACCEPTED,
                        'company_accepted_at' => $now,
                    ])->save();

                    $this->assignmentManager->closeCurrent(
                        $lockedTask,
                        TaskWorkerAssignmentOutcomeEnum::COMPLETED,
                    );

                    TaskStatusUpdated::dispatch(
                        $lockedTask,
                        $fromStatus,
                        TaskStatusEnum::ACCEPTED,
                        null,
                        ['actor_type' => 'system', 'auto_accepted' => true],
                    );

                    $accepted++;
                });
            });

        return $accepted;
    }
}
