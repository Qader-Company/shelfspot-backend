<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Tasks\Application\UseCases\CompanyAcceptTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\CompanyRejectTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\CreateCompanyTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\DeleteCompanyTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\PayDraftTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\RequestTaskRefundUseCase;
use App\Modules\V1\Tasks\Application\UseCases\UpdateCompanyTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Presentation\Http\Requests\CompanyRejectTaskRequest;
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
            relations: $this->taskRepository->listRelations(),
            filters: $filters
        );

        return ApiResponse::success(TaskResource::collection($tasks)->response()->getData(true));
    }

    public function store(StoreCompanyTaskRequest $request, CreateCompanyTaskUseCase $createCompanyTaskUseCase)
    {
        $task = $createCompanyTaskUseCase->execute(
            $request->validated(),
            $request->user(),
            $request->allFiles()
        );

        return ApiResponse::created(new TaskResource($task));
    }

    public function update(int $id, StoreCompanyTaskRequest $request, UpdateCompanyTaskUseCase $updateCompanyTaskUseCase)
    {
        $task = $updateCompanyTaskUseCase->execute(
            $this->getCompanyTask($id),
            $request->validated(),
            $request->allFiles()
        );

        return ApiResponse::updated(new TaskResource($task));
    }

    public function show(int $id)
    {
        $task = $this->getCompanyTask(
            $id,
            $this->taskRepository->detailRelations()
        );
        return ApiResponse::success(new TaskResource($task));
    }

    public function pay(int $id, Request $request, PayDraftTaskUseCase $payDraftTaskUseCase)
    {
        $task = $payDraftTaskUseCase->execute(
            $this->getCompanyTask($id),
            $request->user()
        );

        return ApiResponse::updated(new TaskResource($task));
    }

    public function requestRefund(int $id, Request $request, RequestTaskRefundUseCase $requestTaskRefundUseCase)
    {
        $task = $requestTaskRefundUseCase->execute(
            $this->getCompanyTask($id),
            $request->user()
        );

        return ApiResponse::updated(new TaskResource($task));
    }

    public function accept(int $id, Request $request, CompanyAcceptTaskUseCase $companyAcceptTaskUseCase)
    {
        $task = $companyAcceptTaskUseCase->execute(
            $this->getCompanyTask($id),
            $request->user()
        );

        return ApiResponse::updated(new TaskResource($task));
    }

    public function reject(int $id, CompanyRejectTaskRequest $request, CompanyRejectTaskUseCase $companyRejectTaskUseCase)
    {
        $task = $companyRejectTaskUseCase->execute(
            $this->getCompanyTask($id),
            $request->user(),
            $request->validated('reason')
        );

        return ApiResponse::updated(new TaskResource($task));
    }

    public function destroy(int $id, Request $request, DeleteCompanyTaskUseCase $deleteCompanyTaskUseCase)
    {
        $deleteCompanyTaskUseCase->execute(
            $this->getCompanyTask($id),
            $request->user()
        );

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
