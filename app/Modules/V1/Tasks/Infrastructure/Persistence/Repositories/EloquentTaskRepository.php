<?php

namespace App\Modules\V1\Tasks\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;
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
        $task = Task::create($attributes);
        return $task;
    }

    public function update(Task $task, array $attributes): Task
    {
        $task->update($attributes);
        return $task->refresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function TasksByCoordinates(float $latitude, float $longitude, float $radiusKilometers, array $boundingBox, array $filters = []): CursorPaginator
    {
        $distanceSql = $this->haversineSql();

        return $this->query(
            relations: ['company', 'services'],
            filters: $filters
        )->where('status', TaskStatusEnum::PENDING->value)
        ->where('payment_status', TaskPaymentStatusEnum::CHARGED->value)
        ->whereNull('assigned_worker_id')
        ->whereBetween('latitude', [$boundingBox['min_latitude'], $boundingBox['max_latitude']])
        ->whereBetween('longitude', [$boundingBox['min_longitude'], $boundingBox['max_longitude']])
        ->select('tasks.*')
        ->selectRaw($distanceSql.' as distance_km', [$latitude, $longitude, $latitude])
        ->whereRaw($distanceSql.' <= ?', [$latitude, $longitude, $latitude, $radiusKilometers])
        ->orderBy('distance_km')
        ->cursorPaginate();
    }

    public function assignedToWorker(int $workerId, array $filters = [], array $relations = [], string $paginationType = 'cursor'): LengthAwarePaginator|CursorPaginator
    {
        $query = $this->query($relations, [], $filters)
        ->where('assigned_worker_id', $workerId)
        ->orderByDesc('date')
        ->orderByDesc('id');

        return match ($paginationType) {
            'cursor' => $query->cursorPaginate(),
            'paginate' => $query->paginate()
        };
    }


    public function latestForCompany(int $companyId, int $limit = 15, array $relations = []): Collection
    {
        return $this->query($relations)
            ->where('company_id', $companyId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function countForCompany(int $companyId, ?TaskStatusEnum $status = null): int
    {
        return $this->query()
            ->where('company_id', $companyId)
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->count();
    }

    public function sumTotalPriceForCompany(int $companyId, ?TaskPaymentStatusEnum $paymentStatus = null): float
    {
        return (float) $this->query()
            ->where('company_id', $companyId)
            ->when($paymentStatus, fn (Builder $query) => $query->where('payment_status', $paymentStatus->value))
            ->sum('total_price');
    }

    private function haversineSql(): string
    {
        return sprintf(
            '(%F * acos(least(1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))',
            GeoDistanceCalculator::EARTH_RADIUS_KM
        );
    }

    public function query(array $relations = [], array $relationsCount = [], array $filters = []): Builder
    {
        return Task::query()
            ->whereNull('company_deleted_at')
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }

    public function relations(): array
    {
        return [
            'services.service.translations',
            'services.products.product.media',
            'services.products.product.brand.media',
            'services.products.product.subBrand.media',
            'services.products.product.category',
            'services.products.product.subCategory',
            'creator',
            'company',
            'assignedWorker.user',
        ];
    }
}
