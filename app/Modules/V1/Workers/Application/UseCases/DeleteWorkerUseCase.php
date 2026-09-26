<?php

namespace App\Modules\V1\Workers\Application\UseCases;

use App\Modules\V1\Users\Application\Services\UserAccessRevoker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class DeleteWorkerUseCase
{
    public function __construct(
        private readonly WorkerRepositoryInterface $workerRepository,
        private readonly UserAccessRevoker $userAccessRevoker,
    ) {}

    public function execute(int $workerId): void
    {
        $worker = $this->workerRepository->getById($workerId, ['user']);

        if (! $worker) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        DB::transaction(function () use ($worker): void {
            $this->userAccessRevoker->revoke($worker->user);
            $this->workerRepository->delete($worker);
        });
    }
}
