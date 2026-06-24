<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanCompleteTaskRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;

class CompleteTaskUseCase
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function execute(Task $task, Worker $worker): Task
    {
        return DB::transaction(function () use ($task, $worker) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);
            $lockedTask->loadMissing('services');

            CanCompleteTaskRule::validate($lockedTask, $worker->id);

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::IN_REVIEW,
                'completed_at' => now(),
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $lockedTask->getOriginal('status'),
                TaskStatusEnum::IN_REVIEW,
                $worker,
                ['worker_id' => $worker->id]
            );

            return $lockedTask->refresh();
        });
    }

}
