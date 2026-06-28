<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ForceDeleteCompanyDeletedTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function execute(Task $task): void
    {
        DB::transaction(function () use ($task): void {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->getCompanyDeletedById($task->id, includePurged: true);

            $this->taskRepository->forceDeleteCompanyDeleted($lockedTask);
        });
    }
}
