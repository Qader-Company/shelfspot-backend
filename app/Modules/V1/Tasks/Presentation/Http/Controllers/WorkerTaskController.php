<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Tasks\Application\UseCases\AcceptTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\StartTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Presentation\Http\Requests\StartTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskResource;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WorkerTaskController extends Controller
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function accept(int $id, Request $request, AcceptTaskUseCase $acceptTaskUseCase)
    {
        $task = $acceptTaskUseCase->execute($this->task($id), $this->worker($request));

        return ApiResponse::updated(new TaskResource($task->load(['services.service.translations', 'assignedWorker'])));
    }

    public function start(int $id, StartTaskRequest $request, StartTaskUseCase $startTaskUseCase)
    {
        $task = $startTaskUseCase->execute(
            task: $this->task($id),
            worker: $this->worker($request),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude')
        );

        return ApiResponse::updated(new TaskResource($task->load(['services.service.translations', 'assignedWorker'])));
    }

    private function task(int $id): Task
    {
        $task = $this->taskRepository->getById($id, ['services.service.translations', 'assignedWorker']);

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
