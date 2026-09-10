<?php

namespace App\Modules\V1\WorkersWallets\Domain\Repositories;

use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Domain\Models\WorkerWalletTransaction;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkerWalletRepositoryInterface
{
    public function transactions(int $workerId, array $filters = []): LengthAwarePaginator;

    public function currentBalance(int $workerId): float;

    public function totalEarned(int $workerId): float;

    public function findByIdempotencyKey(string $idempotencyKey): ?WorkerWalletTransaction;

    public function createTransaction(
        Worker $worker,
        WorkerWalletTransactionTypeEnum $type,
        float|int|string $amount,
        string $idempotencyKey,
        ?Model $reference = null,
        ?string $description = null,
    ): WorkerWalletTransaction;
}
