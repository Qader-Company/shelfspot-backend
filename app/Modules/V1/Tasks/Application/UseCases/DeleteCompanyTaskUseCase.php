<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanDeleteTaskRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteCompanyTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    )
    {
    }

    public function execute(Task $task, ?User $actor = null): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);
            CanDeleteTaskRule::validate($lockedTask);
            $fromStatus = $lockedTask->status;

            $lockedTask->forceFill([
                'company_deleted_at' => now(),
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                $fromStatus,
                $actor,
                ['action' => 'company_deleted']
            );

            return $lockedTask->refresh();
        });
    }


}
