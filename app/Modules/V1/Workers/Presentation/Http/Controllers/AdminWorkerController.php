<?php

namespace App\Modules\V1\Workers\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Controllers\ManagesTrash;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Workers\Application\Jobs\SendWorkerCredentialsEmailJob;
use App\Modules\V1\Workers\Application\UseCases\CreateWorkerUseCase;
use App\Modules\V1\Workers\Application\UseCases\DeleteWorkerUseCase;
use App\Modules\V1\Workers\Application\UseCases\ShowAdminWorkerUseCase;
use App\Modules\V1\Workers\Application\UseCases\UpdateWorkerUseCase;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use App\Modules\V1\Workers\Presentation\Http\Requests\AdminShowWorkerRequest;
use App\Modules\V1\Workers\Presentation\Http\Requests\RegisterWorkerRequest;
use App\Modules\V1\Workers\Presentation\Http\Requests\UpdateWorkerRequest;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminWorkerController extends Controller
{
    use Filterable;
    use ManagesTrash;

    public function __construct(
        private readonly WorkerRepositoryInterface $workerRepository,
    ) {}

    public function index(Request $request)
    {
        $workers = $this->workerRepository->getAll(
            relations: ['user'],
            filters: $this->acceptedFilters($request, [
                'is_active',
                'search',
            ])
        );

        return ApiResponse::success(WorkerResource::collection($workers)->response()->getData(true));
    }

    public function store(RegisterWorkerRequest $request, CreateWorkerUseCase $createWorkerUseCase)
    {
        $attributes = $request->validated();
        $user = $createWorkerUseCase->execute($attributes);

        SendWorkerCredentialsEmailJob::dispatch(
            name: $user->name,
            email: $user->email,
            password: $attributes['password'],
        )->onQueue(config('notifications.queues.normal'));

        return ApiResponse::created(new WorkerResource($user));
    }

    public function show(AdminShowWorkerRequest $request, int $worker, ShowAdminWorkerUseCase $showAdminWorkerUseCase)
    {
        return ApiResponse::success(new WorkerResource(
            $showAdminWorkerUseCase->execute($worker, $request->taskFilters())
        ));
    }

    public function update(UpdateWorkerRequest $request, int $worker, UpdateWorkerUseCase $updateWorkerUseCase)
    {
        return ApiResponse::updated(new WorkerResource(
            $updateWorkerUseCase->execute($worker, $request->validated())
        ));
    }

    public function destroy(int $worker, DeleteWorkerUseCase $deleteWorkerUseCase)
    {
        $deleteWorkerUseCase->execute($worker);

        return ApiResponse::deleted();
    }

    protected function trashRepository(): TrashableRepositoryInterface
    {
        return $this->workerRepository;
    }

    protected function trashResourceCollection(LengthAwarePaginator $items): mixed
    {
        return WorkerResource::collection($items)->response()->getData(true);
    }
}
