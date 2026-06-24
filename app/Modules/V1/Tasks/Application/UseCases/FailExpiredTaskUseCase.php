<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanFailExpiredTaskRule;
use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Support\Facades\DB;

class FailExpiredTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder
    ) {
    }

    public function execute(?int $limit = null): int
    {
        $query = $this->taskRepository->query()
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('status', TaskStatusEnum::PENDING->value)
                        ->whereDate('date', '<', now()->toDateString());
                })->orWhere(function ($query) {
                    $query->where('status', TaskStatusEnum::STARTED->value)
                        ->whereNotNull('start_deadline_at')
                        ->where('start_deadline_at', '<', now());
                });
            })
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $failed = 0;

        $query->get()->each(function (Task $task) use (&$failed) {
            DB::transaction(function () use ($task, &$failed) {
                /** @var Task $lockedTask */
                $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

                if (! CanFailExpiredTaskRule::isExpired($lockedTask)) {
                    return;
                }

                $fromStatus = $lockedTask->status;

                $lockedTask->forceFill([
                    'status' => TaskStatusEnum::FAILED,
                ])->save();

                $this->statusHistoryRecorder->record(
                    task: $lockedTask,
                    fromStatus: $fromStatus,
                    toStatus: TaskStatusEnum::FAILED,
                    meta: ['reason' => 'expired']
                );

                $failed++;
            });
        });

        return $failed;
    }
}
