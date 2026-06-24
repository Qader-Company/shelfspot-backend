<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\AbstractTaskActionRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyAcceptTaskUseCase
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function execute(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);
            $fromStatus = $lockedTask->status;

            AbstractTaskActionRule::insureTaskStatusIsOneOf(
                task: $lockedTask,
                statuses: [TaskStatusEnum::COMPLETED, TaskStatusEnum::REJECTED],
                message: ('tasks.validation.accept_completed_or_rejected_only')
            );

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::ACCEPTED,
                'company_accepted_at' => now(),
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::ACCEPTED,
                $actor,
                ['actor_type' => 'company']
            );

            return $lockedTask->refresh();
        });
    }
}
