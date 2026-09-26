<?php

namespace App\Modules\V1\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Payments\Domain\Repositories\AdminPaymentRepositoryInterface;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentAdminPaymentRepository implements AdminPaymentRepositoryInterface
{
    public function paginate(array $filters, ?TaskPaymentStatusEnum $paymentStatus): LengthAwarePaginator
    {
        return $this->paymentQuery($filters)
            ->when($paymentStatus, fn (Builder $query) => $query->where('payment_status', $paymentStatus->value))
            ->latest('created_at')
            ->paginate();
    }

    public function find(int $id): Task
    {
        $payment = $this->paymentQuery()
            ->whereKey($id)
            ->first();

        if (! $payment) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $payment;
    }

    public function summary(array $filters): array
    {
        $summaryQuery = $this->paymentQuery($filters);

        $totalIncoming = (clone $summaryQuery)
            ->where('payment_status', TaskPaymentStatusEnum::CHARGED->value)
            ->sum('total_price');

        $totalOutgoing = (clone $summaryQuery)
            ->where('payment_status', TaskPaymentStatusEnum::REFUNDED->value)
            ->sum('total_price');

        $totalWorkerShare = (clone $summaryQuery)
            ->whereNotNull('settled_at')
            ->sum('worker_share_amount');

        $totalPlatformShare = (clone $summaryQuery)
            ->whereNotNull('settled_at')
            ->sum('platform_share_amount');

        return [
            'total_incoming' => (float) $totalIncoming,
            'total_outgoing' => (float) $totalOutgoing,
            'net_balance' => (float) $totalIncoming - (float) $totalOutgoing,
            'total_worker_share' => (float) $totalWorkerShare,
            'total_platform_share' => (float) $totalPlatformShare,
        ];
    }

    private function paymentQuery(array $filters = []): Builder
    {
        return Task::query()
            ->with('company')
            ->whereIn('payment_status', TaskPaymentStatusEnum::values())
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('company', function (Builder $companyQuery) use ($search): void {
                    $companyQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['company_id'] ?? null, fn (Builder $query, int $companyId) => $query->where('company_id', $companyId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }
}
