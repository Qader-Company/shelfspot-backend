<?php

namespace App\Modules\Shared\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

trait HandlesTrash
{
    abstract protected function trashableModel(): string;

    public function getTrash(array $filters = []): LengthAwarePaginator
    {
        return $this->trashQuery()
            ->onlyTrashed()
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->latest('deleted_at')
            ->paginate(request('per_page', 15));
    }

    public function bulkDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $models = $this->trashQuery()->whereKey($ids)->get();
            $models->each->delete();

            return $models->count();
        });
    }

    public function restore(int $id): bool
    {
        $model = $this->findTrashed($id);

        return $model?->restore() ?? false;
    }

    public function bulkRestore(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $models = $this->trashQuery()->onlyTrashed()->whereKey($ids)->get();
            $models->each->restore();

            return $models->count();
        });
    }

    public function forceDelete(int $id): bool
    {
        $model = $this->findTrashed($id);

        return $model?->forceDelete() ?? false;
    }

    public function bulkForceDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $models = $this->trashQuery()->onlyTrashed()->whereKey($ids)->get();
            $models->each->forceDelete();

            return $models->count();
        });
    }

    private function findTrashed(int $id): ?Model
    {
        return $this->trashQuery()->onlyTrashed()->find($id);
    }

    private function trashQuery(): Builder
    {
        /** @var class-string<Model> $model */
        $model = $this->trashableModel();

        return $model::query();
    }
}
