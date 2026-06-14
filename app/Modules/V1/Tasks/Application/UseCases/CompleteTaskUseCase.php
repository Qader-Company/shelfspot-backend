<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteTaskUseCase
{
    public function __construct(private readonly TaskStatusHistoryRecorder $statusHistoryRecorder)
    {
    }

    public function execute(Task $task, Worker $worker): Task
    {
        return DB::transaction(function () use ($task, $worker) {
            /** @var Task $lockedTask */
            $lockedTask = Task::query()
                ->with('services')
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $lockedTask->status;

            if ($lockedTask->status !== TaskStatusEnum::IN_PROGRESS) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.complete_in_progress_only')]);
            }

            if ((int) $lockedTask->assigned_worker_id !== (int) $worker->id) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.worker_not_assigned')]);
            }

            if ($lockedTask->services->isEmpty()) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.complete_requires_services')]);
            }

            $hasIncompleteServices = $lockedTask->services
                ->contains(fn ($service) => $service->status !== TaskServiceStatusEnum::COMPLETED);

            if ($hasIncompleteServices) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.complete_requires_completed_services')]);
            }

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::COMPLETED,
                'completed_at' => now(),
            ])->save();

            $this->statusHistoryRecorder->record(
                task: $lockedTask,
                fromStatus: $fromStatus,
                toStatus: TaskStatusEnum::COMPLETED,
                actor: $worker->user,
                meta: ['worker_id' => $worker->id]
            );

            return $lockedTask->refresh();
        });
    }
}
