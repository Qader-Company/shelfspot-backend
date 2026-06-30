<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanStartTaskRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;

class StartTaskUseCase
{
    public const START_DEADLINE_MINUTES = 15;

    private const ACTIVE_WORKER_TASK_STATUSES = [
        TaskStatusEnum::STARTED,
        TaskStatusEnum::IN_PROGRESS,
        TaskStatusEnum::REOPENED,
    ];

    public function __construct(private readonly TaskRepositoryInterface $taskRepository,)
    {
    }

    public function execute(Task $task, Worker $worker): Task
    {
        return DB::transaction(function () use ($task, $worker) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);
            $fromStatus = $lockedTask->status;

            Worker::query()->whereKey($worker->id)->lockForUpdate()->firstOrFail();

            CanStartTaskRule::validate(
                task: $lockedTask,
                workerHasActiveTask: $this->workerHasActiveTask($worker)
            );

            $now = now();
            $lockedTask->forceFill([
                'status' => TaskStatusEnum::STARTED,
                'assigned_worker_id' => $worker->id,
                'accepted_at' => $now,
                'start_deadline_at' => $now->copy()->addMinutes(self::START_DEADLINE_MINUTES),
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::STARTED,
                $worker,
                [
                    'worker_id' => $worker->id,
                    'start_deadline_minutes' => self::START_DEADLINE_MINUTES
                ]
            );

            return $lockedTask->refresh();
        });
    }

    private function workerHasActiveTask(Worker $worker): bool
    {
        foreach (self::ACTIVE_WORKER_TASK_STATUSES as $status) {
            if ($this->taskRepository->countAssignedToWorkerByStatus($worker->id, $status) > 0) {
                return true;
            }
        }

        return false;
    }
}
