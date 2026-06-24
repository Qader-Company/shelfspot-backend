<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminReopenTaskUseCase
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function execute(Task $task, ?User $admin = null, ?string $reason = null): Task
    {
        return DB::transaction(function () use ($task, $admin, $reason) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id, ['services']);

            if ($lockedTask->status !== TaskStatusEnum::REJECTED) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.reopen_rejected_only')]);
            }

            $fromStatus = $lockedTask->status;
            $lockedTask->forceFill([
                'status' => TaskStatusEnum::REOPENED,
                'reopened_at' => now(),
                'reopen_reason' => $reason,
                'auto_accept_at' => null,
                'auto_accepted_at' => null,
            ])->save();

            $lockedTask->services()->update(['status' => TaskServiceStatusEnum::PENDING->value]);

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::REOPENED,
                $admin,
                ['actor_type' => 'admin', 'reason' => $reason]
            );

            return $lockedTask->refresh();
        });
    }
}
