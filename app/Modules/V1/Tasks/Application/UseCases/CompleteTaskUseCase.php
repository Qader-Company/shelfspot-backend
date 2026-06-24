<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanCompleteTaskRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Carbon;
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

            $fromStatus = $lockedTask->status;

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::COMPLETED,
                'completed_at' => now(),
                'auto_accept_at' => $this->autoAcceptAt($lockedTask),
                'auto_accepted_at' => null,
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::COMPLETED,
                $worker,
                ['worker_id' => $worker->id]
            );

            return $lockedTask->refresh();
        });
    }

    private function autoAcceptAt(Task $task): Carbon
    {
        $executionTime = $task->execution_time instanceof Carbon
            ? $task->execution_time->format('H:i:s')
            : (string) $task->execution_time;

        return Carbon::parse($task->date->toDateString().' '.$executionTime)
            ->addMinutes((int) $task->estimated_duration_minutes)
            ->addDays(2);
    }

}
