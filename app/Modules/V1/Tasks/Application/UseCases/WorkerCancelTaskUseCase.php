<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanCancelTaskRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkerCancelTaskUseCase
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function execute(Task $task, Worker $worker, string $reason): Task
    {
        return DB::transaction(function () use ($task, $worker, $reason) {

            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

            CanCancelTaskRule::validate($lockedTask, $worker->id);

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::WORKER_CANCELLED,
                'worker_cancelled_at' => now(),
                'worker_cancel_reason' => $reason,
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $lockedTask->getOriginal('status'),
                TaskStatusEnum::WORKER_CANCELLED,
                $worker,
                ['worker_id' => $worker->id, 'reason' => $reason]
            );

            return $lockedTask->refresh();
        });
    }
}
