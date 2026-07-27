<?php

namespace App\Modules\V1\Reports\Application\Services;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    private const DEFAULT_PERIOD = 'week';

    public function dashboard(?string $period = null): array
    {
        $period ??= self::DEFAULT_PERIOD;

        return Cache::remember(
            AdminDashboardCache::key($period),
            now()->addMinute(),
            fn (): array => $this->buildDashboard($period)
        );
    }

    private function buildDashboard(string $period): array
    {
        $now = CarbonImmutable::now();
        $range = $this->rangeFor($period, $now);

        return [
            'period' => $period,
            'range' => $this->serializeRange($range),
            'cards' => $this->cards($now, $range),
            'charts' => [
                'requests_over_time' => $this->requestsOverTime($period, $range),
                'status_distribution' => $this->statusDistribution($range),
            ],
            'top_companies' => $this->topCompanies($range),
            'top_freelancers' => $this->topFreelancers($range),
        ];
    }

    private function cards(CarbonImmutable $now, array $range): array
    {
        $companies = Company::query()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active')
            ->first();

        $workers = Worker::query()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active')
            ->first();

        return [
            'total_companies' => (int) $companies->total,
            'active_companies' => (int) $companies->active,
            'requests_today' => $this->baseTaskQuery()
                ->whereBetween('created_at', [$now->startOfDay(), $now->endOfDay()])
                ->count(),
            'total_freelancers' => (int) $workers->total,
            'active_freelancers' => (int) $workers->active,
            'net_payment_volume' => $this->netPaymentVolume($range),
        ];
    }

    private function requestsOverTime(string $period, array $range): array
    {
        $bucketExpression = $this->bucketExpression($period);
        $totals = $this->baseTaskQuery()
            ->selectRaw("{$bucketExpression} as bucket, COUNT(*) as total")
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        return $this->periodBuckets($period, $range)
            ->map(fn (string $bucket) => [
                'bucket' => $bucket,
                'total' => (int) ($totals[$bucket] ?? 0),
            ])
            ->all();
    }

    private function statusDistribution(array $range): array
    {
        $statuses = $this->baseTaskQuery()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as pending, '.
                'COALESCE(SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END), 0) as in_progress, '.
                'COALESCE(SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END), 0) as completed, '.
                'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as rejected',
                [
                    TaskStatusEnum::PENDING->value,
                    TaskStatusEnum::STARTED->value,
                    TaskStatusEnum::IN_PROGRESS->value,
                    TaskStatusEnum::REOPENED->value,
                    TaskStatusEnum::COMPLETED->value,
                    TaskStatusEnum::ACCEPTED->value,
                    TaskStatusEnum::REJECTED->value,
                ]
            )
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->first();

        return [
            ['status' => 'pending', 'total' => (int) $statuses->pending],
            ['status' => 'in_progress', 'total' => (int) $statuses->in_progress],
            ['status' => 'completed', 'total' => (int) $statuses->completed],
            ['status' => 'rejected', 'total' => (int) $statuses->rejected],
        ];
    }

    private function topCompanies(array $range): array
    {
        $requestCounts = $this->baseTaskQuery()
            ->selectRaw('company_id, COUNT(*) as requests_count')
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->groupBy('company_id');

        $paymentVolumes = $this->paymentVolumeQuery($range)
            ->selectRaw(
                'transactions.company_id, COALESCE(SUM(CASE '.
                'WHEN transactions.type = ? THEN transactions.amount '.
                'WHEN transactions.type = ? THEN -transactions.amount ELSE 0 END), 0) as net_payment_volume',
                [
                    CompanyWalletTransactionTypeEnum::TASK_PAYMENT->value,
                    CompanyWalletTransactionTypeEnum::TASK_REFUND->value,
                ]
            )
            ->groupBy('transactions.company_id');

        return Company::query()
            ->joinSub($requestCounts, 'request_counts', 'request_counts.company_id', '=', 'companies.id')
            ->leftJoinSub($paymentVolumes, 'payment_volumes', 'payment_volumes.company_id', '=', 'companies.id')
            ->orderByDesc('request_counts.requests_count')
            ->orderByDesc('payment_volumes.net_payment_volume')
            ->limit(5)
            ->get([
                'companies.id as company_id',
                'companies.name as company_name',
                'request_counts.requests_count',
                DB::raw('COALESCE(payment_volumes.net_payment_volume, 0) as net_payment_volume'),
            ])
            ->map(fn (object $company) => [
                'company_id' => (int) $company->company_id,
                'company_name' => $company->company_name,
                'requests_count' => (int) $company->requests_count,
                'net_payment_volume' => round((float) $company->net_payment_volume, 2),
            ])
            ->all();
    }

    private function topFreelancers(array $range): array
    {
        return Worker::query()
            ->join('users', 'users.id', '=', 'workers.user_id')
            ->join('task_worker_assignments as assignments', 'assignments.worker_id', '=', 'workers.id')
            ->where('workers.is_active', true)
            ->where('assignments.outcome', 'completed')
            ->whereBetween('assignments.updated_at', [$range['from'], $range['to']])
            ->groupBy('workers.id', 'users.name')
            ->orderByDesc('completed_requests')
            ->orderBy('workers.id')
            ->limit(5)
            ->get([
                'workers.id as worker_id',
                'users.name',
                DB::raw('COUNT(*) as completed_requests'),
            ])
            ->map(fn (object $worker) => [
                'worker_id' => (int) $worker->worker_id,
                'name' => $worker->name,
                'completed_requests' => (int) $worker->completed_requests,
            ])
            ->all();
    }

    private function netPaymentVolume(array $range): float
    {
        $volume = $this->paymentVolumeQuery($range)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN transactions.type = ? THEN transactions.amount '.
                'WHEN transactions.type = ? THEN -transactions.amount ELSE 0 END), 0) as total',
                [
                    CompanyWalletTransactionTypeEnum::TASK_PAYMENT->value,
                    CompanyWalletTransactionTypeEnum::TASK_REFUND->value,
                ]
            )
            ->value('total');

        return round((float) $volume, 2);
    }

    private function paymentVolumeQuery(array $range): \Illuminate\Database\Query\Builder
    {
        return DB::table('company_wallet_transactions as transactions')
            ->join('companies', 'companies.id', '=', 'transactions.company_id')
            ->whereNull('companies.deleted_at')
            ->whereIn('transactions.type', [
                CompanyWalletTransactionTypeEnum::TASK_PAYMENT->value,
                CompanyWalletTransactionTypeEnum::TASK_REFUND->value,
            ])
            ->whereBetween('transactions.created_at', [$range['from'], $range['to']]);
    }

    private function baseTaskQuery(): Builder
    {
        return Task::query()
            ->whereNull('company_deleted_at')
            ->whereNull('company_purged_at')
            ->whereHas('company');
    }

    private function rangeFor(string $period, CarbonImmutable $now): array
    {
        return match ($period) {
            'month' => ['from' => $now->startOfMonth(), 'to' => $now->endOfMonth()],
            'year' => ['from' => $now->startOfYear(), 'to' => $now->endOfYear()],
            default => ['from' => $now->startOfWeek(), 'to' => $now->endOfWeek()],
        };
    }

    private function serializeRange(array $range): array
    {
        return [
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
        ];
    }

    private function bucketExpression(string $period): string
    {
        if ($period !== 'year') {
            return 'DATE(created_at)';
        }

        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";
    }

    private function periodBuckets(string $period, array $range): Collection
    {
        $cursor = $period === 'year'
            ? $range['from']->startOfMonth()
            : $range['from']->startOfDay();
        $end = $period === 'year'
            ? $range['to']->startOfMonth()
            : $range['to']->startOfDay();

        $buckets = collect();

        while ($cursor->lessThanOrEqualTo($end)) {
            $buckets->push($period === 'year' ? $cursor->format('Y-m') : $cursor->toDateString());
            $cursor = $period === 'year' ? $cursor->addMonth() : $cursor->addDay();
        }

        return $buckets;
    }
}
