<?php

namespace App\Modules\V1\Tasks\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)
            ->paginate();
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Task
    {
        return $this->query($relations, $relationsCount)
            ->where('id', $id)
            ->first();
    }

    public function getByIdAndLockedForUpdate(int $id, array $relations = [], array $relationsCount = []): ?Task
    {
        return $this->query($relations, $relationsCount)
            ->where('id', $id)
            ->lockForUpdate()
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

    public function getCompanyTrash(int $companyId, array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->companyDeletedQuery($relations, $relationsCount, $filters)
            ->where('company_id', $companyId)
            ->paginate();
    }

    public function getCompanyDeletedForAdmin(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->companyDeletedQuery($relations, $relationsCount, $filters, includePurged: true)
            ->paginate();
    }

    public function getCompanyDeletedById(int $id, array $relations = [], bool $includePurged = false): ?Task
    {
        return $this->companyDeletedQuery($relations, includePurged: $includePurged)
            ->whereKey($id)
            ->first();
    }

    public function restoreCompanyDeleted(Task $task): Task
    {
        $task->forceFill([
            'company_deleted_at' => null,
            'company_purged_at' => null,
        ])->save();

        return $task->refresh();
    }

    public function purgeForCompany(Task $task): Task
    {
        $task->forceFill([
            'company_purged_at' => now(),
        ])->save();

        return $task->refresh();
    }

    public function forceDeleteCompanyDeleted(Task $task): void
    {
        $task->delete();
    }

    public function tasksByCoordinates(float $latitude, float $longitude, float $radiusKilometers, array $boundingBox, array $filters = []): CursorPaginator
    {
        $distanceSql = $this->haversineSql();

        return $this->query(
            relations: ['company'],
            relationsCount: $this->listRelationsCount(),
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

    public function assignedToWorker(int $workerId, array $filters = [], array $relations = [], array $relationsCount = [], string $paginationType = 'cursor'): LengthAwarePaginator|CursorPaginator
    {
        $reassignedToWorker = (bool) ($filters['reassigned_to_me'] ?? false);
        $taskFilters = Arr::except($filters, ['reassigned_to_me']);

        $query = $this->query($relations, $relationsCount, $taskFilters)
            ->leftJoin('task_worker_assignments as current_assignments', function ($join) {
                $join->on('current_assignments.task_id', '=', 'tasks.id')
                    ->whereNull('current_assignments.unassigned_at');
            })
            ->where('tasks.assigned_worker_id', $workerId)
            ->when(
                $reassignedToWorker,
                fn (Builder $query) => $query->whereExists(function ($assignments) use ($workerId) {
                    $assignments->selectRaw('1')
                        ->from('task_worker_assignments as worker_reassignments')
                        ->whereColumn('worker_reassignments.task_id', 'tasks.id')
                        ->where('worker_reassignments.worker_id', $workerId)
                        ->whereIn('worker_reassignments.assignment_type', [
                            TaskWorkerAssignmentTypeEnum::REASSIGNED->value,
                            TaskWorkerAssignmentTypeEnum::REOPENED_REASSIGNED->value,
                        ]);
                })
            )
            ->select('tasks.*')
            ->selectRaw(
                'CASE
                    WHEN tasks.status = ? THEN 0
                    WHEN current_assignments.assignment_type IN (?, ?, ?) THEN 1
                    ELSE 2
                END AS worker_priority_rank',
                [
                    TaskStatusEnum::IN_PROGRESS->value,
                    TaskWorkerAssignmentTypeEnum::REOPENED_SAME_WORKER->value,
                    TaskWorkerAssignmentTypeEnum::REOPENED_REASSIGNED->value,
                    TaskWorkerAssignmentTypeEnum::REASSIGNED->value,
                ]
            )
            ->selectRaw('COALESCE(current_assignments.assigned_at, tasks.updated_at) AS worker_priority_at')
            ->orderBy('worker_priority_rank')
            ->orderByDesc('worker_priority_at')
            ->orderByDesc('tasks.id');

        return match ($paginationType) {
            'cursor' => $query->cursorPaginate(),
            'paginate' => $query->paginate()
        };
    }

    public function assignedTaskForWorker(int $taskId, int $workerId, array $relations = []): ?Task
    {
        return $this->query($relations)
            ->whereKey($taskId)
            ->where('assigned_worker_id', $workerId)
            ->first();
    }

    public function assignedToWorkerForAdmin(int $workerId, array $filters = [], array $relations = []): Collection
    {
        $queryFilters = collect($filters)->except('not_in_progress')->all();

        return $this->query($relations, [], $queryFilters)
            ->where('assigned_worker_id', $workerId)
            ->when(
                (bool) ($filters['not_in_progress'] ?? false),
                fn (Builder $query) => $query->where('status', '!=', TaskStatusEnum::IN_PROGRESS->value)
            )
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }

    public function countAssignedToWorkerByStatus(int $workerId, TaskStatusEnum $status): int
    {
        return $this->query()
            ->where('assigned_worker_id', $workerId)
            ->where('status', $status->value)
            ->count();
    }

    public function inProgressAssignedToWorker(int $workerId, array $relations = []): ?Task
    {
        return $this->query($relations)
            ->where('assigned_worker_id', $workerId)
            ->where('status', TaskStatusEnum::IN_PROGRESS->value)
            ->latest('started_at')
            ->latest('id')
            ->first();
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
            ->whereNull('company_purged_at')
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }

    private function companyDeletedQuery(array $relations = [], array $relationsCount = [], array $filters = [], bool $includePurged = false): Builder
    {
        return Task::query()
            ->whereNotNull('company_deleted_at')
            ->when(! $includePurged, fn (Builder $query) => $query->whereNull('company_purged_at'))
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }

    public function listRelations(): array
    {
        return [
            'company',
            'assignedWorker.user',
        ];
    }

    public function listRelationsCount(): array
    {
        return ['services'];
    }

    public function detailRelations(): array
    {
        return [
            'services.service.translations',
            'services.media',
            'services.submission',
            'services.submission.media',
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

    public function relations(): array
    {
        return $this->detailRelations();
    }
}
