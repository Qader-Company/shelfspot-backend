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
    public const EXPIRED_PENDING_REASON = 'expired';
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder
    ) {
    }

    public function execute(?int $limit = null): int
    {
        $query = $this->taskRepository->query()
            ->whereIn('status', [
                TaskStatusEnum::PENDING->value,
                TaskStatusEnum::WORKER_CANCELLED->value,
            ])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('expires_at')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $failed = 0;

        $query->get(['id'])->each(function (Task $task) use (&$failed) {
            DB::transaction(function () use ($task, &$failed) {
                /** @var Task $lockedTask */
                $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

                if (! CanFailExpiredTaskRule::isExpired($lockedTask)) {
                    return;
                }

                $fromStatus = $lockedTask->status;
                $toStatus = TaskStatusEnum::FAILED;

                $lockedTask->forceFill(['status' => $toStatus])->save();

                $this->statusHistoryRecorder->record(
                    task: $lockedTask,
                    fromStatus: $fromStatus,
                    toStatus: $toStatus,
                    meta: ['reason' => self::EXPIRED_PENDING_REASON]
                );

                $failed++;
            });
        });

        return $failed;
    }
}
