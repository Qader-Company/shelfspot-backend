<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanStartTaskRule;
use App\Modules\V1\Tasks\Application\Services\TaskWorkerAssignmentManager;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;

class StartTaskUseCase
{
    public const START_DEADLINE_MINUTES = 15;

    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskWorkerAssignmentManager $assignmentManager,
    ) {}

    public function execute(Task $task, Worker $worker): Task
    {
        return DB::transaction(function () use ($task, $worker) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);
            $fromStatus = $lockedTask->status;

            Worker::query()->whereKey($worker->id)->lockForUpdate()->firstOrFail();

            CanStartTaskRule::validate(
                task: $lockedTask,
                worker: $worker,
                workerHasActiveTask: $this->workerHasAnotherActiveTask($worker, $lockedTask),
            );

            $now = now();
            $lockedTask->forceFill([
                'status' => TaskStatusEnum::STARTED,
                'assigned_worker_id' => $worker->id,
                'accepted_at' => $now,
                'start_deadline_at' => $now->copy()->addMinutes(self::START_DEADLINE_MINUTES),
            ])->save();

            if ($fromStatus === TaskStatusEnum::PENDING) {
                $this->assignmentManager->assign(
                    $lockedTask,
                    $worker,
                    TaskWorkerAssignmentTypeEnum::INITIAL,
                    $worker->user,
                );
            }

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::STARTED,
                $worker,
                [
                    'worker_id' => $worker->id,
                    'start_deadline_minutes' => self::START_DEADLINE_MINUTES,
                ]
            );

            return $lockedTask->refresh();
        });
    }

    private function workerHasAnotherActiveTask(Worker $worker, Task $task): bool
    {
        return $this->taskRepository->query()
            ->where('assigned_worker_id', $worker->id)
            ->whereKeyNot($task->id)
            ->whereIn('status', TaskStatusEnum::values(TaskStatusEnum::workerActiveStatuses()))
            ->exists();
    }
}
