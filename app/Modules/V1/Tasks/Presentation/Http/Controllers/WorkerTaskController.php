<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Tasks\Application\UseCases\AcceptTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\CompleteTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\StartTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\SubmitTaskServiceUseCase;
use App\Modules\V1\Tasks\Application\UseCases\WorkerCancelTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Presentation\Http\Requests\NearbyTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Requests\StartTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Requests\SubmitTaskServiceRequest;
use App\Modules\V1\Tasks\Presentation\Http\Requests\WorkerCancelTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskResource;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskServiceSubmissionResource;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WorkerTaskController extends Controller
{
    use Filterable;
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function nearbyTasks(NearbyTaskRequest $request, GeoDistanceCalculator $geoDistanceCalculator,)
    {
        $worker = $this->worker($request);
        $latitude = $worker->last_latitude;
        $longitude = $worker->last_longitude;

        if ($latitude === null || $longitude === null) {
            throw new AccessDeniedHttpException(__('api.location_required'));
        }

        $radius = $request->radiusKilometers();

        $tasks = $this->taskRepository->TasksByCoordinates(
            latitude: $latitude,
            longitude: $longitude,
            radiusKilometers: $radius,
            boundingBox: $geoDistanceCalculator->boundingBox($latitude, $longitude, $radius),
            filters: Arr::only($request->validated(), ['execution_date'])
        );

        return ApiResponse::success(
            [
                'radius_km' => $radius ?? NearbyTaskRequest::DEFAULT_RADIUS_KM,
                'tasks' => TaskResource::collection($tasks)
                    ->response()
                    ->getData(true)
            ]
        );
    }

    public function mine(Request $request)
    {
        $tasks = $this->taskRepository->assignedToWorker(
            workerId: $this->worker($request)->id,
            filters: $this->acceptedFilters($request, ['status', 'date_from', 'date_to']),
            relations: ['services.service.translations', 'assignedWorker']
        );

        return ApiResponse::success(
            TaskResource::collection($tasks)
                ->response()
                ->getData(true)
        );
    }

    public function accept(int $id, Request $request, AcceptTaskUseCase $acceptTaskUseCase)
    {
        $task = $acceptTaskUseCase->execute(
            $this->task($id),
            $this->worker($request)
        );

        return ApiResponse::updated(
            new TaskResource($task)
        );
    }

    public function start(int $id, StartTaskRequest $request, StartTaskUseCase $startTaskUseCase)
    {
        $task = $startTaskUseCase->execute(
            task: $this->task($id),
            worker: $this->worker($request),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude')
        );

        return ApiResponse::updated(new TaskResource($task->load(['services.service.translations', 'services.products.product', 'services.submission', 'assignedWorker'])));
    }

    public function submitService(int $id, int $serviceId, SubmitTaskServiceRequest $request, SubmitTaskServiceUseCase $submitTaskServiceUseCase)
    {
        $submission = $submitTaskServiceUseCase->execute(
            task: $request->taskModel(),
            taskService: $request->taskServiceModel(),
            worker: $request->workerModel(),
            formData: $request->formData(),
            filesByField: $request->submissionFiles()
        );

        return ApiResponse::updated(new TaskServiceSubmissionResource($submission));
    }

    public function complete(int $id, Request $request, CompleteTaskUseCase $completeTaskUseCase)
    {
        $task = $completeTaskUseCase->execute(
            $this->task($id),
            $this->worker($request)
        );

        return ApiResponse::message(__('api.updated'));
    }

    public function cancel(int $id, WorkerCancelTaskRequest $request, WorkerCancelTaskUseCase $workerCancelTaskUseCase)
    {
        $task = $workerCancelTaskUseCase->execute(
            task: $this->task($id),
            worker: $this->worker($request),
            reason: $request->validated('reason')
        );

        return ApiResponse::message(__('api.updated'));
    }

    private function task(int $id, array $relations = []): Task
    {
        $task = $this->taskRepository->getById($id, $relations);

        if (! $task) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $task;
    }

    private function worker(Request $request): Worker
    {
        $worker = $request->user()?->worker;

        if (! $worker || ! $worker->is_active) {
            throw new AccessDeniedHttpException(__('api.forbidden'));
        }

        return $worker;
    }
}
