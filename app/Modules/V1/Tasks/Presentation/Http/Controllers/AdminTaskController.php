<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Tasks\Application\UseCases\AdminReassignTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Presentation\Http\Requests\AdminReassignTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Requests\AdminTaskIndexRequest;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskResource;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminTaskController extends Controller
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly WorkerRepositoryInterface $workerRepository
    )
    {
    }

    public function index(AdminTaskIndexRequest $request)
    {
        $tasks = $this->taskRepository->getAll(
            relations: $this->taskRepository->relations(),
            filters: $request->filters()
        );

        return ApiResponse::success(
            TaskResource::collection($tasks)
                ->response()
                ->getData(true)
        );
    }

    public function show(int $id)
    {
        return ApiResponse::success(new TaskResource(
            $this->task($id, $this->taskRepository->relations())
        ));
    }

    public function reassign(int $id, AdminReassignTaskRequest $request, AdminReassignTaskUseCase $adminReassignTaskUseCase)
    {
        $worker = $this->workerRepository->getById($request->validated('worker_id'));

        $task = $adminReassignTaskUseCase->execute(
            task: $this->task($id),
            worker: $worker,
            admin: $request->user()
        );

        return ApiResponse::updated(new TaskResource($task->load(['services.service.translations', 'services.products.product', 'services.submission', 'assignedWorker'])));
    }

    private function task(int $id, array $relations = ['services.service.translations', 'assignedWorker']): Task
    {
        $task = $this->taskRepository->getById($id, $relations);

        if (! $task) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $task;
    }
}
