<?php

namespace App\Modules\V1\WorkersWallets\Domain\Repositories;

use App\Modules\V1\WorkersWallets\Domain\Models\WithdrawalRequest;
use Illuminate\Pagination\LengthAwarePaginator;

interface WithdrawalRequestRepositoryInterface
{
    public function forWorker(int $workerId, array $filters = []): LengthAwarePaginator;

    public function findForWorker(int $workerId, int $withdrawalId, bool $lockForUpdate = false): ?WithdrawalRequest;

    public function forAdmin(array $filters = []): LengthAwarePaginator;

    public function findForAdmin(int $withdrawalId, bool $lockForUpdate = false): ?WithdrawalRequest;

    public function create(array $attributes): WithdrawalRequest;

    public function workerSummary(int $workerId): array;

    public function adminSummary(array $filters = []): array;
}
