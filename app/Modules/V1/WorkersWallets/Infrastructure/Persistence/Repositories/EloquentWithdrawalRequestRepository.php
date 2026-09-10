<?php

namespace App\Modules\V1\WorkersWallets\Infrastructure\Persistence\Repositories;

use App\Modules\V1\WorkersWallets\Domain\Models\WithdrawalRequest;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WithdrawalRequestRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentWithdrawalRequestRepository implements WithdrawalRequestRepositoryInterface
{
    public function forWorker(int $workerId, array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)
            ->where('worker_id', $workerId)
            ->latest('id')
            ->paginate();
    }

    public function findForWorker(int $workerId, int $withdrawalId, bool $lockForUpdate = false): ?WithdrawalRequest
    {
        return WithdrawalRequest::query()
            ->where('worker_id', $workerId)
            ->whereKey($withdrawalId)
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate())
            ->first();
    }

    public function forAdmin(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with(['worker.user', 'processedBy'])
            ->latest('id')
            ->paginate();
    }

    public function findForAdmin(int $withdrawalId, bool $lockForUpdate = false): ?WithdrawalRequest
    {
        return WithdrawalRequest::query()
            ->with(['worker.user', 'processedBy'])
            ->whereKey($withdrawalId)
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate())
            ->first();
    }

    public function create(array $attributes): WithdrawalRequest
    {
        return WithdrawalRequest::query()->create($attributes);
    }

    public function workerSummary(int $workerId): array
    {
        $query = WithdrawalRequest::query()->where('worker_id', $workerId);

        return [
            'pending_withdrawals' => (float) (clone $query)
                ->where('status', WithdrawalStatusEnum::PENDING->value)
                ->sum('amount'),
            'total_withdrawn' => (float) (clone $query)
                ->where('status', WithdrawalStatusEnum::PAID->value)
                ->sum('amount'),
        ];
    }

    public function adminSummary(array $filters = []): array
    {
        $query = $this->query($filters, includeStatusFilter: false);

        return [
            'total_requested_amount' => (float) (clone $query)->sum('amount'),
            'pending_amount' => (float) (clone $query)
                ->where('status', WithdrawalStatusEnum::PENDING->value)
                ->sum('amount'),
            'paid_amount' => (float) (clone $query)
                ->where('status', WithdrawalStatusEnum::PAID->value)
                ->sum('amount'),
            'total_requests_count' => (clone $query)->count(),
            'pending_requests_count' => (clone $query)
                ->where('status', WithdrawalStatusEnum::PENDING->value)
                ->count(),
        ];
    }

    private function query(array $filters = [], bool $includeStatusFilter = true): Builder
    {
        return WithdrawalRequest::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->whereHas('worker', function (Builder $workerQuery) use ($search) {
                    $workerQuery
                        ->where('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($filters['worker_id'] ?? null, fn (Builder $query, int $workerId) => $query->where('worker_id', $workerId))
            ->when($includeStatusFilter && ($filters['status'] ?? null), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['method'] ?? null, fn (Builder $query, string $method) => $query->where('method', $method))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }
}
