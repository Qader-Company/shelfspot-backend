<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Support\Facades\DB;

class FailExpiredTaskUseCase
{
    public function __construct(private readonly TaskStatusHistoryRecorder $statusHistoryRecorder)
    {
    }

    public function execute(?int $limit = null): int
    {
        $query = Task::query()
            ->whereNull('company_deleted_at')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('status', TaskStatusEnum::PENDING->value)
                        ->whereDate('date', '<', now()->toDateString());
                })->orWhere(function ($query) {
                    $query->where('status', TaskStatusEnum::ACCEPTED->value)
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
                $lockedTask = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

                if (! $this->isExpired($lockedTask)) {
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

    private function isExpired(Task $task): bool
    {
        if ($task->status === TaskStatusEnum::PENDING) {
            return $task->date->isBefore(now()->startOfDay());
        }

        return $task->status === TaskStatusEnum::ACCEPTED
            && $task->start_deadline_at !== null
            && $task->start_deadline_at->isPast();
    }
}
