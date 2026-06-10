<?php

namespace App\Modules\V1\Workers\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentWorkerRepository implements WorkerRepositoryInterface
{
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

    private function query(array $relations = [], array $relationsCount = [], array $filters = []): Builder
    {
        return Worker::query()
            ->when(array_key_exists('is_active', $filters), fn (Builder $query) => $query->where('is_active', (bool) $filters['is_active']))
            ->when($filters['phone'] ?? null, fn (Builder $query, string $phone) => $query->where('phone', $phone))
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('phone', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $query) use ($search) {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }
}
