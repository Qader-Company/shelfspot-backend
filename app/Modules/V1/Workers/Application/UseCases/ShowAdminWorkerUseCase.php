<?php

namespace App\Modules\V1\Workers\Application\UseCases;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowAdminWorkerUseCase
{
    private const TASK_RELATIONS = [
        'services.service.translations',
        'services.products.product',
        'services.submission',
        'assignedWorker',
    ];

    public function __construct(
        private readonly WorkerRepositoryInterface $workerRepository,
        private readonly TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function execute(int $workerId, array $taskFilters = []): Worker
    {
        $worker = $this->workerRepository->getById($workerId, ['user']);

        if (! $worker) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        $worker->setRelation(
            'assignedTasks',
            $this->taskRepository->assignedToWorkerForAdmin($worker->id, $taskFilters, self::TASK_RELATIONS)
        );

        $worker->admin_task_counts = $this->taskCounts($worker->id);
        $worker->in_progress_task_completion_percentage = $this->inProgressCompletionPercentage($worker->id);

        return $worker;
    }

    private function taskCounts(int $workerId): array
    {
        return [
            'total_accepted' => $this->taskRepository->countAssignedToWorkerByStatus($workerId, TaskStatusEnum::STARTED),
            'total_completed' => $this->taskRepository->countAssignedToWorkerByStatus($workerId, TaskStatusEnum::IN_REVIEW),
            'total_cancelled' => $this->taskRepository->countAssignedToWorkerByStatus($workerId, TaskStatusEnum::WORKER_CANCELLED),
        ];
    }

    private function inProgressCompletionPercentage(int $workerId): ?float
    {
        $task = $this->taskRepository->inProgressAssignedToWorker($workerId, ['services.submission']);

        if (! $task) {
            return null;
        }

        return $this->completionPercentage($task);
    }

    private function completionPercentage(Task $task): float
    {
        $totalServices = $task->services->count();

        if ($totalServices === 0) {
            return 0.0;
        }

        $submittedServices = $task->services
            ->filter(fn ($service) => $service->submission !== null)
            ->count();

        return round(($submittedServices / $totalServices) * 100, 2);
    }
}
