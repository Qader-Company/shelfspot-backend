<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Support\Facades\DB;

class AutoAcceptExpiredReviewTasksUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder,
    ) {
    }

    public function execute(?int $limit = null): int
    {
        $query = Task::query()
            ->where('status', TaskStatusEnum::COMPLETED->value)
            ->whereNotNull('auto_accept_at')
            ->where('auto_accept_at', '<=', now())
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $accepted = 0;

        $query->get()->each(function (Task $task) use (&$accepted) {
            DB::transaction(function () use ($task, &$accepted) {
                $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

                if ($lockedTask->status !== TaskStatusEnum::COMPLETED
                    || $lockedTask->auto_accept_at === null
                    || now()->lessThan($lockedTask->auto_accept_at)) {
                    return;
                }

                $fromStatus = $lockedTask->status;
                $now = now();

                $lockedTask->forceFill([
                    'status' => TaskStatusEnum::ACCEPTED,
                    'company_accepted_at' => $now,
                    'auto_accepted_at' => $now,
                ])->save();

                $this->statusHistoryRecorder->record(
                    task: $lockedTask,
                    fromStatus: $fromStatus,
                    toStatus: TaskStatusEnum::ACCEPTED,
                    meta: ['actor_type' => 'system', 'auto_accepted' => true]
                );

                $accepted++;
            });
        });

        return $accepted;
    }
}
