<?php

namespace App\Modules\V1\Workers\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\Shared\Presentation\Http\Controllers\ManagesTrash;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Workers\Application\Jobs\SendWorkerCredentialsEmailJob;
use App\Modules\V1\Workers\Application\UseCases\CreateWorkerUseCase;
use App\Modules\V1\Workers\Application\UseCases\ShowAdminWorkerUseCase;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use App\Modules\V1\Workers\Presentation\Http\Requests\AdminShowWorkerRequest;
use App\Modules\V1\Workers\Presentation\Http\Requests\RegisterWorkerRequest;
use App\Modules\V1\Workers\Presentation\Http\Requests\UpdateWorkerRequest;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AdminWorkerController extends Controller
{
    use Filterable;
    use ManagesTrash;

    public function __construct(
        private readonly WorkerRepositoryInterface $workerRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

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

    public function update(UpdateWorkerRequest $request, int $worker)
    {
        $worker = $this->getWorker($worker, ['user']);
        $data = $request->validated();

        DB::transaction(function () use ($worker, $data) {
            $userAttributes = Arr::only($data, ['name', 'email', 'password']);
            $workerAttributes = Arr::only($data, ['phone', 'is_active']);

            if ($userAttributes !== []) {
                $this->userRepository->update($worker->user, $userAttributes);
            }

            if ($workerAttributes !== []) {
                $this->workerRepository->update($worker, $workerAttributes);
            }

            if (isset($data['image'])) {
                $worker->addMedia($data['image'])->toMediaCollection('image');
            }
        });

        return ApiResponse::updated(new WorkerResource($worker->refresh()->load('user')));
    }

    public function destroy(int $worker)
    {
        $worker = $this->getWorker($worker, ['user']);
        $this->workerRepository->delete($worker);

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

    private function getWorker(int $id, array $relations = []): Worker
    {
        $worker = $this->workerRepository->getById($id, $relations);

        if (! $worker) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $worker;
    }
}
