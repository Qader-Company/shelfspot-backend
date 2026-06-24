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
    public const EXPIRED_START_DEADLINE_REASON = 'start_deadline_expired';
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

                $toStatus = $fromStatus === TaskStatusEnum::STARTED
                    ? TaskStatusEnum::PENDING
                    : TaskStatusEnum::FAILED;

                $attributes = ['status' => $toStatus];
                $reason = self::EXPIRED_PENDING_REASON;

                if ($fromStatus === TaskStatusEnum::STARTED) {
                    $attributes += [
                        'assigned_worker_id' => null,
                        'accepted_at' => null,
                        'start_deadline_at' => null,
                        'start_deadline_extension_minutes' => null,
                        'start_deadline_extended_at' => null,
                    ];
                    $reason = self::EXPIRED_START_DEADLINE_REASON;
                }

                $lockedTask->forceFill($attributes)->save();

                $this->statusHistoryRecorder->record(
                    task: $lockedTask,
                    fromStatus: $fromStatus,
                    toStatus: $toStatus,
                    meta: ['reason' => $reason]
                );

                $failed++;
            });
        });

        return $failed;
    }
}
