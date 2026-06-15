<?php

namespace App\Modules\V1\Workers\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Infrastructure\Persistence\Repositories\HandlesTrash;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentWorkerRepository implements WorkerRepositoryInterface
{
    use HandlesTrash;

    protected function trashableModel(): string
    {
        return Worker::class;
    }

    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)
            ->latest()
            ->paginate(request('per_page', 15));
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Worker
    {
        return $this->query($relations, $relationsCount)
            ->whereKey($id)
            ->first();
    }

    public function findByUserId(int $userId, array $relations = []): ?Worker
    {
        return Worker::query()
            ->with($relations)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $attributes): Worker
    {
        return DB::transaction(fn () => Worker::create($attributes));
    }

    public function update(Worker $worker, array $attributes): Worker
    {
        return DB::transaction(function () use ($worker, $attributes) {
            $worker->update($attributes);

            return $worker;
        });
    }

    public function delete(Worker $worker): void
    {
        $worker->delete();
    }

    public function availableNearTask(float $latitude, float $longitude, float $radiusKilometers, array $boundingBox): Collection
    {
        $distanceSql = $this->haversineSql();

        return $this->query(['user'])
            ->where('is_active', true)
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->whereBetween('last_latitude', [$boundingBox['min_latitude'], $boundingBox['max_latitude']])
            ->whereBetween('last_longitude', [$boundingBox['min_longitude'], $boundingBox['max_longitude']])
            ->whereDoesntHave('assignedTasks', fn (Builder $query) => $query->where('status', TaskStatusEnum::IN_PROGRESS->value))
            ->select('workers.*')
            ->selectRaw($distanceSql.' as distance_km', [$latitude, $longitude, $latitude])
            ->whereRaw($distanceSql.' <= ?', [$latitude, $longitude, $latitude, $radiusKilometers])
            ->orderBy('distance_km')
            ->get();
    }

    private function haversineSql(): string
    {
        return sprintf(
            '(%F * acos(least(1, cos(radians(?)) * cos(radians(last_latitude)) * cos(radians(last_longitude) - radians(?)) + sin(radians(?)) * sin(radians(last_latitude)))))',
            GeoDistanceCalculator::EARTH_RADIUS_KM
        );
    }

    private function query(array $relations = [], array $relationsCount = [], array $filters = []): Builder
    {
        return Worker::query()
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }
}
