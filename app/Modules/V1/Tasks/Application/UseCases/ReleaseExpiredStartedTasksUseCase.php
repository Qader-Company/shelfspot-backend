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

class ReleaseExpiredStartedTasksUseCase
{
    public const EXPIRED_START_DEADLINE_REASON = 'start_deadline_expired';

    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskWorkerAssignmentManager $assignmentManager,
    ) {}

    public function execute(?int $limit = null): int
    {
        $query = $this->taskRepository->query()
            ->where('status', TaskStatusEnum::STARTED->value)
            ->whereNotNull('start_deadline_at')
            ->where('start_deadline_at', '<', now())
            ->reorder();

        $released = 0;

        $query->lazyById(TaskSchedulerBatch::CHUNK_SIZE)
            ->take(TaskSchedulerBatch::limit($limit))
            ->each(function (Task $task) use (&$released) {
                DB::transaction(function () use ($task, &$released) {
                    /** @var Task $lockedTask */
                    $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

                    if ($lockedTask->status !== TaskStatusEnum::STARTED
                        || $lockedTask->start_deadline_at === null
                        || ! $lockedTask->start_deadline_at->isPast()) {
                        return;
                    }

                    $fromStatus = $lockedTask->status;
                    $toStatus = TaskStatusEnum::PENDING;
                    $previousWorkerId = $lockedTask->assigned_worker_id;

                    $lockedTask->forceFill([
                        'status' => $toStatus,
                        'assigned_worker_id' => null,
                        'accepted_at' => null,
                        'start_deadline_at' => null,
                        'start_deadline_extension_minutes' => null,
                        'start_deadline_extended_at' => null,
                    ])->save();

                    $this->assignmentManager->closeCurrent(
                        $lockedTask,
                        TaskWorkerAssignmentOutcomeEnum::START_DEADLINE_EXPIRED,
                        self::EXPIRED_START_DEADLINE_REASON,
                    );

                    TaskStatusUpdated::dispatch(
                        $lockedTask,
                        $fromStatus,
                        $toStatus,
                        null,
                        [
                            'reason' => self::EXPIRED_START_DEADLINE_REASON,
                            'previous_worker_id' => $previousWorkerId,
                        ],
                    );

                    $released++;
                });
            });

        return $released;
    }
}
