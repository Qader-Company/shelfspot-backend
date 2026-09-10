<?php

namespace App\Modules\V1\WorkersWallets\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Domain\Models\WorkerWalletTransaction;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WorkerWalletRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\Services\WorkerWalletBalanceCalculator;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentWorkerWalletRepository implements WorkerWalletRepositoryInterface
{
    public function transactions(int $workerId, array $filters = []): LengthAwarePaginator
    {
        return $this->queryForWorker($workerId, $filters)
            ->with('reference')
            ->latest('id')
            ->paginate();
    }

    public function latestTransactions(int $workerId, int $limit = 10): Collection
    {
        return $this->queryForWorker($workerId)
            ->with('reference')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function currentBalance(int $workerId): float
    {
        return (float) Worker::query()->whereKey($workerId)->value('wallet_balance');
    }

    public function totalEarned(int $workerId): float
    {
        return (float) $this->queryForWorker($workerId)
            ->where('type', WorkerWalletTransactionTypeEnum::TASK_EARNING->value)
            ->sum('amount');
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?WorkerWalletTransaction
    {
        return WorkerWalletTransaction::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    public function createTransaction(
        Worker $worker,
        WorkerWalletTransactionTypeEnum $type,
        float|int|string $amount,
        string $idempotencyKey,
        ?Model $reference = null,
        ?string $description = null,
    ): WorkerWalletTransaction {
        return DB::transaction(function () use ($worker, $type, $amount, $idempotencyKey, $reference, $description) {
            $lockedWorker = Worker::withTrashed()->whereKey($worker->id)->lockForUpdate()->firstOrFail();

            $existing = $this->findByIdempotencyKey($idempotencyKey);
            if ($existing) {
                return $existing;
            }

            $balanceAfter = WorkerWalletBalanceCalculator::calculate(
                $lockedWorker->wallet_balance,
                $amount,
                $type,
            );

            $transaction = WorkerWalletTransaction::query()->create([
                'worker_id' => $lockedWorker->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'idempotency_key' => $idempotencyKey,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
            ]);

            $lockedWorker->forceFill(['wallet_balance' => $balanceAfter])->save();
            $worker->setAttribute('wallet_balance', $balanceAfter);

            return $transaction;
        });
    }

    private function queryForWorker(int $workerId, array $filters = []): Builder
    {
        return WorkerWalletTransaction::query()
            ->where('worker_id', $workerId)
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }
}
