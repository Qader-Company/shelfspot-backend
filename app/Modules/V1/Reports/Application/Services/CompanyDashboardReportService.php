<?php

namespace App\Modules\V1\Reports\Application\Services;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CompanyDashboardReportService
{
    private const DEFAULT_PERIOD = 'week';

    private const ACTIVE_STATUSES = [
        TaskStatusEnum::PENDING,
        TaskStatusEnum::STARTED,
        TaskStatusEnum::IN_PROGRESS,
        TaskStatusEnum::REOPENED,
    ];

    public function dashboard(int $companyId, ?string $period = null): array
    {
        $selectedPeriod = $period ?: self::DEFAULT_PERIOD;
        $now = CarbonImmutable::now();
        $range = $this->rangeFor($selectedPeriod, $now);
        $previousRange = $this->previousRangeFor($selectedPeriod, $range['from']);

        $activeRequests = $this->activeRequests($companyId);
        $previousActiveRequests = $this->activeRequests($companyId, $previousRange['from'], $previousRange['to']);
        $completedRequests = $this->completedRequests($companyId, $range['from'], $range['to']);
        $previousCompletedRequests = $this->completedRequests($companyId, $previousRange['from'], $previousRange['to']);
        $delayedRequests = $this->delayedRequests($companyId, $now);
        $previousDelayedRequests = $this->delayedRequests($companyId, $previousRange['to']);
        $acceptanceRate = $this->acceptanceRate($companyId, $range['from'], $range['to']);
        $previousAcceptanceRate = $this->acceptanceRate($companyId, $previousRange['from'], $previousRange['to']);

        return [
            'period' => $selectedPeriod,
            'range' => $this->serializeRange($range),
            'cards' => [
                'active_requests' => $this->metric($activeRequests, $previousActiveRequests),
                'completed_this_period' => $this->metric($completedRequests, $previousCompletedRequests),
                'delayed_requests' => $this->metric($delayedRequests, $previousDelayedRequests),
                'acceptance_rate' => $this->metric($acceptanceRate, $previousAcceptanceRate, true),
            ],
            'charts' => [
                'requests_over_time' => $this->requestsOverTime($companyId, $now),
                'status_distribution' => $this->statusDistribution($companyId),
            ],
        ];
    }

    private function activeRequests(int $companyId, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): int
    {
        return $this->countByStatuses($companyId, self::ACTIVE_STATUSES, $from, $to);
    }

    private function completedRequests(int $companyId, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return $this->countByStatuses(
            $companyId,
            [TaskStatusEnum::COMPLETED, TaskStatusEnum::ACCEPTED],
            $from,
            $to
        );
    }

    private function delayedRequests(int $companyId, CarbonImmutable $comparisonDate): int
    {
        return $this->baseQuery($companyId)
            ->whereIn('status', TaskStatusEnum::values(self::ACTIVE_STATUSES))
            ->where(function (Builder $query) use ($comparisonDate) {
                $query->where('date', '<', $comparisonDate->toDateString())
                    ->orWhere('expires_at', '<', $comparisonDate);
            })
            ->count();
    }

    private function acceptanceRate(int $companyId, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $reviewedQuery = $this->baseQuery($companyId)
            ->whereIn('status', [TaskStatusEnum::ACCEPTED->value, TaskStatusEnum::REJECTED->value])
            ->whereBetween('created_at', [$from, $to]);

        $reviewed = (clone $reviewedQuery)->count();

        if ($reviewed === 0) {
            return 0.0;
        }

        $accepted = (clone $reviewedQuery)
            ->where('status', TaskStatusEnum::ACCEPTED->value)
            ->count();

        return round(($accepted / $reviewed) * 100, 2);
    }

    private function requestsOverTime(int $companyId, CarbonImmutable $now): array
    {
        $totalsByMonth = $this->baseQuery($companyId)
            ->selectRaw('MONTH(date) as month_number, COUNT(*) as total')
            ->whereYear('date', $now->year)
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('total', 'month_number');

        return collect(range(1, 12))
            ->map(fn (int $month) => [
                'month' => $month,
                'total' => (int) ($totalsByMonth[$month] ?? 0),
            ])
            ->all();
    }

    private function statusDistribution(int $companyId): array
    {
        $totalsByStatus = $this->baseQuery($companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(TaskStatusEnum::cases())
            ->map(fn (TaskStatusEnum $status) => [
                'status' => $status->value,
                'total' => (int) ($totalsByStatus[$status->value] ?? 0),
            ])
            ->all();
    }

    private function countByStatuses(int $companyId, array $statuses, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): int
    {
        return $this->baseQuery($companyId)
            ->whereIn('status', TaskStatusEnum::values($statuses))
            ->when($from && $to, fn (Builder $query) => $query->whereBetween('created_at', [$from, $to]))
            ->count();
    }

    private function baseQuery(int $companyId): Builder
    {
        return Task::query()
            ->where('company_id', $companyId)
            ->whereNull('company_deleted_at')
            ->whereNull('company_purged_at');
    }

    private function rangeFor(string $period, CarbonImmutable $now): array
    {
        return match ($period) {
            'month' => ['from' => $now->startOfMonth(), 'to' => $now->endOfMonth()],
            'year' => ['from' => $now->startOfYear(), 'to' => $now->endOfYear()],
            default => ['from' => $now->startOfWeek(), 'to' => $now->endOfWeek()],
        };
    }

    private function previousRangeFor(string $period, CarbonImmutable $currentFrom): array
    {
        return match ($period) {
            'month' => [
                'from' => $currentFrom->subMonthNoOverflow()->startOfMonth(),
                'to' => $currentFrom->subMonthNoOverflow()->endOfMonth(),
            ],
            'year' => [
                'from' => $currentFrom->subYear()->startOfYear(),
                'to' => $currentFrom->subYear()->endOfYear(),
            ],
            default => [
                'from' => $currentFrom->subWeek()->startOfWeek(),
                'to' => $currentFrom->subWeek()->endOfWeek(),
            ],
        };
    }

    private function serializeRange(array $range): array
    {
        return [
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
        ];
    }

    private function metric(int|float $current, int|float $previous, bool $percentage = false): array
    {
        $changePercentage = $previous == 0
            ? ($current > 0 ? 100 : 0)
            : (($current - $previous) / $previous) * 100;

        return [
            'value' => $percentage ? round((float) $current, 2) : (int) $current,
            'previous_value' => $percentage ? round((float) $previous, 2) : (int) $previous,
            'change_percentage' => round($changePercentage, 2),
            'trend' => $changePercentage <=> 0,
        ];
    }
}
