<?php

namespace App\Modules\V1\Workers\Application\UseCases;

use App\Modules\V1\Users\Application\Services\UserAccessRevoker;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateWorkerUseCase
{
    public function __construct(
        private readonly WorkerRepositoryInterface $workerRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserAccessRevoker $userAccessRevoker,
    ) {}

    public function execute(int $workerId, array $attributes): Worker
    {
        $worker = $this->workerRepository->getById($workerId, ['user']);

        if (! $worker) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return DB::transaction(function () use ($worker, $attributes): Worker {
            $userAttributes = Arr::only($attributes, ['name', 'email', 'password']);
            $workerAttributes = Arr::only($attributes, ['phone', 'is_active']);
            $willBeDeactivated = array_key_exists('is_active', $workerAttributes)
                && ! (bool) $workerAttributes['is_active'];

            if ($userAttributes !== []) {
                $this->userRepository->update($worker->user, $userAttributes);
            }

            if ($workerAttributes !== []) {
                $this->workerRepository->update($worker, $workerAttributes);
            }

            if (isset($attributes['image'])) {
                $worker->addMedia($attributes['image'])->toMediaCollection('image');
            }

            if ($willBeDeactivated) {
                $this->userAccessRevoker->revoke($worker->user);
            }

            return $worker->refresh()->load('user');
        });
    }
}
