<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Support\Facades\DB;

class MarkOverdueInProgressTasksUseCase
{
    public const OVERDUE_REASON = 'in_progress_estimated_duration_exceeded';

    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder,
    ) {
    }

    public function execute(?int $limit = null): int
    {
        $query = $this->taskRepository->query()
            ->where('status', TaskStatusEnum::IN_PROGRESS->value)
            ->whereNotNull('expected_completion_at')
            ->whereNull('in_progress_overdue_at')
            ->where('expected_completion_at', '<=', now())
            ->orderBy('expected_completion_at')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $marked = 0;

        $query->get(['id'])->each(function (Task $task) use (&$marked) {
            DB::transaction(function () use ($task, &$marked) {
                /** @var Task $lockedTask */
                $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

                if ($lockedTask->status !== TaskStatusEnum::IN_PROGRESS
                    || $lockedTask->expected_completion_at === null
                    || $lockedTask->in_progress_overdue_at !== null
                    || now()->lessThan($lockedTask->expected_completion_at)) {
                    return;
                }

                $now = now();

                $lockedTask->forceFill([
                    'in_progress_overdue_at' => $now,
                ])->save();

                $this->statusHistoryRecorder->record(
                    task: $lockedTask,
                    fromStatus: TaskStatusEnum::IN_PROGRESS,
                    toStatus: TaskStatusEnum::IN_PROGRESS,
                    meta: [
                        'reason' => self::OVERDUE_REASON,
                        'expected_completion_at' => $lockedTask->expected_completion_at->toDateTimeString(),
                        'marked_overdue_at' => $now->toDateTimeString(),
                    ]
                );

                $marked++;
            });
        });

        return $marked;
    }
}
