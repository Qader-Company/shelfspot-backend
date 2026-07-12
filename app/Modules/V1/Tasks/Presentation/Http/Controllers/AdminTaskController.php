<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Tasks\Application\UseCases\AdminReassignTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\AdminReopenTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Presentation\Http\Requests\AdminReassignTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Requests\AdminReopenTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Requests\AdminTaskIndexRequest;
use App\Modules\V1\Tasks\Presentation\Http\Requests\AvailableTaskWorkersRequest;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskResource;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminTaskController extends Controller
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly WorkerRepositoryInterface $workerRepository
    ) {}

    public function index(AdminTaskIndexRequest $request)
    {
        $tasks = $this->taskRepository->getAll(
            relations: $this->taskRepository->listRelations(),
            filters: $request->filters()
        );

        return ApiResponse::success(
            TaskResource::collection($tasks)
                ->response()
                ->getData(true)
        );
    }

    public function companyDeleted(AdminTaskIndexRequest $request)
    {
        $tasks = $this->taskRepository->getCompanyDeletedForAdmin(
            relations: $this->taskRepository->listRelations(),
            filters: $request->filters()
        );

        return ApiResponse::success(
            TaskResource::collection($tasks)
                ->response()
                ->getData(true)
        );
    }

    public function showCompanyDeleted(int $id)
    {
        return ApiResponse::success(new TaskResource(
            $this->companyDeletedTask($id, $this->taskRepository->detailRelations())
        ));
    }

    public function forceDeleteCompanyDeleted(int $id, ForceDeleteCompanyDeletedTaskUseCase $forceDeleteCompanyDeletedTaskUseCase)
    {
        $forceDeleteCompanyDeletedTaskUseCase->execute($this->companyDeletedTask($id));

        return ApiResponse::message(__('api.force_deleted'));
    }

    public function show(int $id)
    {
        return ApiResponse::success(new TaskResource(
            $this->task($id, $this->taskRepository->detailRelations())
        ));
    }

    public function availableWorkers(int $id, AvailableTaskWorkersRequest $request, GeoDistanceCalculator $geoDistanceCalculator)
    {
        $task = $this->task($id);

        $radius = $request->radiusKilometers();
        $workers = $this->workerRepository->availableNearTask(
            latitude: (float) $task->latitude,
            longitude: (float) $task->longitude,
            radiusKilometers: $radius,
            boundingBox: $geoDistanceCalculator->boundingBox((float) $task->latitude, (float) $task->longitude, $radius)
        );

        return ApiResponse::success([
            'radius_km' => $radius,
            'workers' => WorkerResource::collection($workers)->response()->getData(true),
        ]);
    }

    public function reopen(int $id, AdminReopenTaskRequest $request, AdminReopenTaskUseCase $adminReopenTaskUseCase)
    {
        $task = $adminReopenTaskUseCase->execute(
            task: $this->task($id),
            admin: $request->user(),
            reason: $request->validated('reason')
        );

        return ApiResponse::updated(new TaskResource($task->load(['services.service.translations', 'services.products.product', 'services.submission', 'assignedWorker'])));
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

    private function companyDeletedTask(int $id, array $relations = ['services.service.translations', 'assignedWorker']): Task
    {
        $task = $this->taskRepository->getCompanyDeletedById($id, $relations, includePurged: true);

        if (! $task) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $task;
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
