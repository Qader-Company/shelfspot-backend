<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use Illuminate\Support\Facades\DB;

class RestoreCompanyTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function execute(Task $task): Task
    {
        return DB::transaction(function () use ($task) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->getCompanyDeletedById($task->id);

            return $this->taskRepository->restoreCompanyDeleted($lockedTask);
        });
    }
}
