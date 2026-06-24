<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExtendStartDeadlineUseCase
{
    public const ALLOWED_EXTENSION_MINUTES = [5, 10, 15];

    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function execute(Task $task, Worker $worker, int $minutes): Task
    {
        return DB::transaction(function () use ($task, $worker, $minutes) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

            if (! in_array($minutes, self::ALLOWED_EXTENSION_MINUTES, true)) {
                throw ValidationException::withMessages(['minutes' => __('tasks.validation.start_extension_minutes')]);
            }

            if ($lockedTask->status !== TaskStatusEnum::STARTED) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.start_extension_started_only')]);
            }

            if ((int) $lockedTask->assigned_worker_id !== (int) $worker->id) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.worker_not_assigned')]);
            }

            if ($lockedTask->start_deadline_at === null || now()->greaterThan($lockedTask->start_deadline_at)) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.start_deadline_expired')]);
            }

            if ($lockedTask->start_deadline_extended_at !== null) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.start_extension_once')]);
            }

            $originalDeadline = $lockedTask->start_deadline_at->copy();
            $lockedTask->forceFill([
                'start_deadline_at' => $originalDeadline->copy()->addMinutes($minutes),
                'start_deadline_extension_minutes' => $minutes,
                'start_deadline_extended_at' => now(),
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                TaskStatusEnum::STARTED,
                TaskStatusEnum::STARTED,
                $worker,
                [
                    'worker_id' => $worker->id,
                    'reason' => 'start_deadline_extended',
                    'extension_minutes' => $minutes,
                    'previous_start_deadline_at' => $originalDeadline->toDateTimeString(),
                    'new_start_deadline_at' => $lockedTask->start_deadline_at->toDateTimeString(),
                ]
            );

            return $lockedTask->refresh();
        });
    }
}
