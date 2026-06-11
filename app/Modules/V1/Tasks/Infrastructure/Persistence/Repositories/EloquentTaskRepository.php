<?php

namespace App\Modules\V1\Tasks\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentTaskRepository implements TaskRepositoryInterface
{

    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)
            ->paginate(request('per_page', 15));
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Task
    {
        return $this->query($relations, $relationsCount)
            ->where('id', $id)
            ->first();
    }

    public function create(array $attributes): Task
    {
        return DB::transaction(function () use ($attributes) {
            $task = Task::create($attributes);

            return $task;
        });
    }

    public function update(Task $task, array $attributes): Task
    {
        return DB::transaction(function () use ($task, $attributes) {
            $task->update($attributes);

            return $task;
        });
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function availableNearWorker(float $latitude, float $longitude, float $radiusKilometers, array $boundingBox, array $filters = []): LengthAwarePaginator
    {
        $distanceSql = $this->haversineSql();

        return Task::query()
            ->with(['company'])
            ->where('status', TaskStatusEnum::PENDING->value)
            ->where('payment_status', TaskPaymentStatusEnum::CHARGED->value)
            ->when(isset($filters['execution_date']), fn (Builder $query) => $query->whereDate('date', $filters['execution_date']))
            ->whereNull('assigned_worker_id')
            ->whereNull('company_deleted_at')
            ->whereBetween('latitude', [$boundingBox['min_latitude'], $boundingBox['max_latitude']])
            ->whereBetween('longitude', [$boundingBox['min_longitude'], $boundingBox['max_longitude']])
            ->select('tasks.*')
            ->paginate(request('per_page', 15));
    }

    public function assignedToWorker(int $workerId, array $filters = [], array $relations = []): LengthAwarePaginator
    {
        return Task::query()
            ->where('assigned_worker_id', $workerId)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(($filters['status'] ?? null) === null && ($filters['statuses'] ?? []) !== [], fn (Builder $query) => $query->whereIn('status', $filters['statuses']))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $dateFrom) => $query->whereDate('date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $dateTo) => $query->whereDate('date', '<=', $dateTo))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(request('per_page', 15));
    }

    private function haversineSql(): string
    {
        return sprintf(
            '(%F * acos(least(1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))',
            GeoDistanceCalculator::EARTH_RADIUS_KM
        );
    }

    private function query(array $relations = [], array $relationsCount = [], array $filters = []): Builder
    {
        return Task::query()
            ->when($filters['company_id'] ?? null, fn (Builder $query, int $companyId) => $query->where('company_id', $companyId))
            ->whereNull('company_deleted_at')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(($filters['status'] ?? null) === null && ($filters['statuses'] ?? []) !== [], fn (Builder $query) => $query->whereIn('status', $filters['statuses']))
            ->when($filters['payment_status'] ?? null, fn (Builder $query, string $paymentStatus) => $query->where('payment_status', $paymentStatus))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $dateFrom) => $query->whereDate('date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $dateTo) => $query->whereDate('date', '<=', $dateTo))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }
}
