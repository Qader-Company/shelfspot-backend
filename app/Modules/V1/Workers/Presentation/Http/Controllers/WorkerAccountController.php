<?php

namespace App\Modules\V1\Workers\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use App\Modules\V1\Workers\Presentation\Http\Requests\UpdateWorkerLocationRequest;
use App\Modules\V1\Workers\Presentation\Http\Requests\UpdateWorkerRequest;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WorkerAccountController extends Controller
{
    public function __construct(
        private readonly WorkerRepositoryInterface $workerRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function profile(Request $request)
    {
        return ApiResponse::success(new WorkerResource(
            $this->worker($request)->load(['user', 'priorityTasks.currentWorkerAssignment'])
        ));
    }

    public function updateProfile(UpdateWorkerRequest $request)
    {
        $worker = $this->worker($request)->load('user');
        $data = $request->validated();

        DB::transaction(function () use ($worker, $data) {
            $userAttributes = Arr::only($data, ['name', 'email', 'password']);
            $workerAttributes = Arr::only($data, ['phone']);

            if ($userAttributes !== []) {
                $this->userRepository->update($worker->user, $userAttributes);
            }

            if ($workerAttributes !== []) {
                $this->workerRepository->update($worker, $workerAttributes);
            }
        });

        return ApiResponse::updated(new WorkerResource($worker->refresh()->load('user')));
    }

    public function deleteAccount(Request $request)
    {
        $this->userRepository->delete($request->user());

        $this->workerRepository->delete($this->worker($request));

        return ApiResponse::deleted();
    }

    public function updateLocation(UpdateWorkerLocationRequest $request)
    {
        $worker = $this->worker($request);
        $this->workerRepository->update($worker, [
            'last_latitude' => $request->validated('latitude'),
            'last_longitude' => $request->validated('longitude'),
            'location_updated_at' => now(),
        ]);

        return ApiResponse::updated(new WorkerResource($worker->refresh()->load('user')));
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
