<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredStartedTasksUseCase
{
    public const EXPIRED_START_DEADLINE_REASON = 'start_deadline_expired';

    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder
    ) {
    }

    public function execute(?int $limit = null): int
    {
        $query = $this->taskRepository->query()
            ->where('status', TaskStatusEnum::STARTED->value)
            ->whereNotNull('start_deadline_at')
            ->where('start_deadline_at', '<', now())
            ->orderBy('start_deadline_at')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $released = 0;

        $query->get(['id'])->each(function (Task $task) use (&$released) {
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

                $lockedTask->forceFill([
                    'status' => $toStatus,
                    'assigned_worker_id' => null,
                    'accepted_at' => null,
                    'start_deadline_at' => null,
                    'start_deadline_extension_minutes' => null,
                    'start_deadline_extended_at' => null,
                ])->save();

                $this->statusHistoryRecorder->record(
                    task: $lockedTask,
                    fromStatus: $fromStatus,
                    toStatus: $toStatus,
                    meta: ['reason' => self::EXPIRED_START_DEADLINE_REASON]
                );

                $released++;
            });
        });

        return $released;
    }
}
