<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteCompanyTaskUseCase
{
    public function __construct(private readonly TaskStatusHistoryRecorder $statusHistoryRecorder)
    {
    }

    public function execute(Task $task, ?User $actor = null): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            /** @var Task $lockedTask */
            $lockedTask = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $lockedTask->status;

            $lockedTask->forceFill([
                'company_deleted_at' => now(),
            ])->save();

            $this->statusHistoryRecorder->record(
                task: $lockedTask,
                fromStatus: $fromStatus,
                toStatus: TaskStatusEnum::COMPANY_DELETED,
                actor: $actor,
                meta: ['visibility_only' => true]
            );

            return $lockedTask->refresh();
        });
    }
}
