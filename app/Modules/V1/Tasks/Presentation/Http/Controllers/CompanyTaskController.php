<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Tasks\Application\UseCases\CreateCompanyTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\DeleteCompanyTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Presentation\Http\Requests\StoreCompanyTaskRequest;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CompanyTaskController extends Controller
{
    use Filterable;
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function index(Request $request, CreateCompanyTaskUseCase $createCompanyTaskUseCase)
    {
        $filters = $this->acceptedFilters($request, ['status', 'payment_status', 'date_from', 'date_to']);
        $tasks = $this->taskRepository
        ->getAll(
            relations: $createCompanyTaskUseCase->relations(),
            filters: $filters
        );

        return ApiResponse::success(TaskResource::collection($tasks)->response()->getData(true));
    }

    public function store(StoreCompanyTaskRequest $request, CreateCompanyTaskUseCase $createCompanyTaskUseCase)
    {
        $task = $createCompanyTaskUseCase->execute($request->validated(), $request->allFiles());

        return ApiResponse::message(__('api.created'));
    }

    public function show(int $id, CreateCompanyTaskUseCase $createCompanyTaskUseCase)
    {
        $task = $this->getCompanyTask($id, $createCompanyTaskUseCase->relations());

        return ApiResponse::success(new TaskResource($task));
    }

    public function destroy(int $id, DeleteCompanyTaskUseCase $deleteCompanyTaskUseCase)
    {
        $deleteCompanyTaskUseCase->execute($this->getCompanyTask($id), auth()->user());

        return ApiResponse::deleted();
    }

    private function getCompanyTask(int $id, array $relations = []): Task
    {
        $task = $this->taskRepository->getById($id, $relations);

        if (! $task || $task->company_id !== $this->tenantContext->getCompanyId()) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $task;
    }
}
