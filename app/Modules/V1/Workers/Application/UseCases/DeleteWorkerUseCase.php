<?php

namespace App\Modules\V1\Workers\Application\UseCases;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Application\Services\UserAccessRevoker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteWorkerUseCase
{
    public function __construct(
        private readonly WorkerRepositoryInterface $workerRepository,
        private readonly UserAccessRevoker $userAccessRevoker,
    ) {}

    public function execute(int $workerId): void
    {
        DB::transaction(function () use ($workerId): void {
            $worker = $this->workerRepository->getByIdAndLockedForUpdate($workerId, ['user']);

            if (! $worker) {
                throw new ModelNotFoundException(__('api.not_found'));
            }

            if ($worker->assignedTasks()
                ->whereIn('status', TaskStatusEnum::values(TaskStatusEnum::workerActiveStatuses()))
                ->exists()) {
                throw ValidationException::withMessages([
                    'worker' => __('tasks.validation.delete_worker_with_active_task'),
                ]);
            }

            $this->userAccessRevoker->revoke($worker->user);
            $this->workerRepository->delete($worker);
        });
    }
}
