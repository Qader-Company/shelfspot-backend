<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteTaskUseCase
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function execute(Task $task, Worker $worker): Task
    {
        return DB::transaction(function () use ($task, $worker) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->query(['services'])
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $lockedTask->status;

            $this->ensureTaskIsAvailableToBeAccepted($lockedTask, $worker);

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::COMPLETED,
                'completed_at' => now(),
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

    private function ensureTaskIsAvailableToBeAccepted($lockedTask, $workerId)
    {
        if ($lockedTask->status !== TaskStatusEnum::IN_PROGRESS) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.complete_in_progress_only')]);
        }

        if ((int) $lockedTask->assigned_worker_id !== (int) $workerId) {
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
    }
}
