<?php

namespace App\Modules\Shared\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Application\Jobs\ForceDeleteCatalogItemJob;
use App\Modules\Shared\Application\Jobs\RestoreCatalogItemJob;
use App\Modules\Shared\Application\Jobs\SoftDeleteCatalogItemJob;
use App\Modules\Shared\Domain\ValueObjects\CatalogPurgeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait CascadesCatalogTrashActions
{
    abstract protected function trashableModel(): string;

    /**
     * @return array<int, string>
     */
    abstract protected function trashCascadeRelations(): array;

    public function getTrash(array $filters = []): LengthAwarePaginator
    {
        return $this->trashCascadeQuery()
            ->onlyTrashed()
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->latest('deleted_at')
            ->paginate();
    }

    public function bulkDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $modelIds = $this->trashCascadeQuery()->whereKey($ids)->pluck($this->trashKeyName())->all();
            $this->queueDeleteWithCatalogChildren($modelIds);

            return count($modelIds);
        });
    }

    public function restore(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $model = $this->findTrashCascadeModel($id);

            if (! $model) {
                return false;
            }

            $this->ensureRestoreAllowed($model);
            $this->queueRestoreWithCatalogChildren([$model->getKey()]);

            return true;
        });
    }

    public function bulkRestore(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $models = $this->trashCascadeQuery()->onlyTrashed()->whereKey($ids)->get();
            $models->each(fn (Model $model) => $this->ensureRestoreAllowed($model));

            $modelIds = $models->modelKeys();
            $this->queueRestoreWithCatalogChildren($modelIds);

            return count($modelIds);
        });
    }

    public function forceDelete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $model = $this->findTrashCascadeModel($id);

            if (! $model) {
                return false;
            }

            $this->queueForceDelete(collect([$model]));

            return true;
        });
    }

    public function bulkForceDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $models = $this->trashCascadeQuery()->onlyTrashed()->whereKey($ids)->get();
            $this->queueForceDelete($models);

            return $models->count();
        });
    }

    protected function deleteWithCatalogChildren(Model $model): void
    {
        $this->queueDeleteWithCatalogChildren([$model->getKey()]);
    }

    /**
     * @param array<int, int> $modelIds
     */
    private function queueDeleteWithCatalogChildren(array $modelIds): void
    {
        if ($modelIds === []) {
            return;
        }

        SoftDeleteCatalogItemJob::dispatch(
            $this->trashableModel(),
            $modelIds,
            $this->trashCascadeRelations(),
        )->afterCommit();
    }

    /**
     * @param array<int, int> $modelIds
     */
    private function queueRestoreWithCatalogChildren(array $modelIds): void
    {
        if ($modelIds === []) {
            return;
        }

        RestoreCatalogItemJob::dispatch(
            $this->trashableModel(),
            $modelIds,
            $this->trashCascadeRelations(),
        )->afterCommit();
    }

    private function trashKeyName(): string
    {
        /** @var class-string<Model> $model */
        $model = $this->trashableModel();

        return (new $model())->getKeyName();
    }

    public function usesQueuedDelete(): bool
    {
        return true;
    }

    public function usesQueuedRestore(): bool
    {
        return true;
    }

    public function usesQueuedForceDelete(): bool
    {
        return true;
    }

    private function ensureRestoreAllowed(Model $model): void
    {
        if (CatalogPurgeStatus::blocksRestore($model->getAttribute('purge_status'))) {
            throw new ConflictHttpException(__('api.restore_blocked_by_purge'));
        }
    }

    /**
     * @param Collection<int, Model> $models
     */
    private function queueForceDelete(Collection $models): void
    {
        $queueableModels = $models->filter(
            fn (Model $model): bool => CatalogPurgeStatus::canQueue($model->getAttribute('purge_status'))
        );

        if ($queueableModels->isEmpty()) {
            return;
        }

        $queueableModels->each(function (Model $model): void {
            $model->forceFill([
                'purge_status' => CatalogPurgeStatus::QUEUED,
                'purge_failure_reason' => null,
            ])->save();

            foreach ($this->trashCascadeRelations() as $relation) {
                $model->{$relation}()
                    ->withTrashed()
                    ->update([
                        'purge_status' => CatalogPurgeStatus::QUEUED,
                        'purge_failure_reason' => null,
                    ]);
            }
        });

        ForceDeleteCatalogItemJob::dispatch(
            $this->trashableModel(),
            $queueableModels->map(fn (Model $model) => $model->getKey())->values()->all(),
            $this->trashCascadeRelations(),
        )->afterCommit();
    }

    private function findTrashCascadeModel(int $id): ?Model
    {
        return $this->trashCascadeQuery()->onlyTrashed()->find($id);
    }

    private function trashCascadeQuery(): Builder
    {
        /** @var class-string<Model> $model */
        $model = $this->trashableModel();

        return $model::query();
    }
}
