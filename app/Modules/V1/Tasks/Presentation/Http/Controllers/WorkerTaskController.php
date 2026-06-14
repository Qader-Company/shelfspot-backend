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
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Presentation\Http\Requests\StartTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Requests\SubmitTaskServiceRequest;
use App\Modules\V1\Tasks\Presentation\Http\Requests\WorkerCancelTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskResource;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskServiceSubmissionResource;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WorkerTaskController extends Controller
{
    use Filterable;
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function mine(Request $request)
    {
        $filters = $this->acceptedFilters($request, ['status', 'date_from', 'date_to']);
        $tasks = $this->taskRepository->assignedToWorker(
            workerId: $this->worker($request)->id,
            filters: $filters,
            relations: ['services.service.translations', 'services.products.product', 'services.submission', 'assignedWorker']
        );

        return ApiResponse::success(TaskResource::collection($tasks)->response()->getData(true));
    }

    public function accept(int $id, Request $request, AcceptTaskUseCase $acceptTaskUseCase)
    {
        $task = $acceptTaskUseCase->execute($this->task($id), $this->worker($request));

        return ApiResponse::updated(new TaskResource($task->load(['services.service.translations', 'services.products.product', 'services.submission', 'assignedWorker'])));
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
            task: $this->task($id),
            taskService: $this->taskService($serviceId),
            worker: $this->worker($request),
            formData: $request->formData(),
            filesByField: $request->submissionFiles()
        );

        return ApiResponse::updated(new TaskServiceSubmissionResource($submission));
    }

    public function complete(int $id, Request $request, CompleteTaskUseCase $completeTaskUseCase)
    {
        $task = $completeTaskUseCase->execute($this->task($id), $this->worker($request));

        return ApiResponse::updated(new TaskResource($task->load(['services.service.translations', 'services.products.product', 'services.submission', 'assignedWorker'])));
    }

    public function cancel(int $id, WorkerCancelTaskRequest $request, WorkerCancelTaskUseCase $workerCancelTaskUseCase)
    {
        $task = $workerCancelTaskUseCase->execute(
            task: $this->task($id),
            worker: $this->worker($request),
            reason: $request->validated('reason')
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

    private function taskService(int $id): TaskService
    {
        $taskService = TaskService::query()->with(['service.translations', 'products'])->find($id);

        if (! $taskService) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $taskService;
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
