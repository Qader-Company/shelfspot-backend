<?php

namespace App\Modules\Shared\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait HandlesTrash
{
    abstract protected function trashableModel(): string;

    public function getTrash(array $filters = []): LengthAwarePaginator
    {
        return $this->trashQuery()
            ->onlyTrashed()
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->latest('deleted_at')
            ->paginate();
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

        if ($model && $model->getAttribute('purge_status') === 'queued') {
            throw new ConflictHttpException(__('api.restore_blocked_by_purge'));
        }

        return $model?->restore() ?? false;
    }

    public function bulkRestore(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $models = $this->trashQuery()->onlyTrashed()->whereKey($ids)->get();

            if ($models->contains(fn (Model $model): bool => $model->getAttribute('purge_status') === 'queued')) {
                throw new ConflictHttpException(__('api.restore_blocked_by_purge'));
            }

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
