<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Tasks\Application\UseCases\AdminReassignTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Presentation\Http\Requests\AdminReassignTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskResource;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminTaskController extends Controller
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function reassign(int $id, AdminReassignTaskRequest $request, AdminReassignTaskUseCase $adminReassignTaskUseCase)
    {
        $worker = Worker::query()->findOrFail($request->validated('worker_id'));

        $task = $adminReassignTaskUseCase->execute(
            task: $this->task($id),
            worker: $worker,
            admin: $request->user()
        );

        return ApiResponse::updated(new TaskResource($task->load(['services.service.translations', 'services.products.product', 'services.submission', 'assignedWorker'])));
    }

    private function task(int $id): Task
    {
        $task = $this->taskRepository->getById($id, ['services.service.translations', 'assignedWorker']);

        if (! $task) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $task;
    }
}
