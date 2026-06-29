<?php

namespace App\Modules\Shared\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Application\Jobs\ForceDeleteCatalogItemJob;
use App\Modules\Shared\Domain\ValueObjects\CatalogPurgeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
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
            $models = $this->trashCascadeQuery()->whereKey($ids)->get();
            $models->each(fn (Model $model) => $this->deleteWithCatalogChildren($model));

            return $models->count();
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

            $model->restore();
            $this->restoreCatalogChildren($model);

            return true;
        });
    }

    public function bulkRestore(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $models = $this->trashCascadeQuery()->onlyTrashed()->whereKey($ids)->get();
            $models->each(fn (Model $model) => $this->ensureRestoreAllowed($model));
            $models->each(function (Model $model): void {
                $model->restore();
                $this->restoreCatalogChildren($model);
            });

            return $models->count();
        });
    }

    public function forceDelete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $model = $this->findTrashCascadeModel($id);

            if (! $model) {
                return false;
            }

            $this->queueForceDelete($model);

            return true;
        });
    }

    public function bulkForceDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $models = $this->trashCascadeQuery()->onlyTrashed()->whereKey($ids)->get();
            $models->each(fn (Model $model) => $this->queueForceDelete($model));

            return $models->count();
        });
    }

    protected function deleteWithCatalogChildren(Model $model): void
    {
        DB::transaction(function () use ($model): void {
            foreach ($this->trashCascadeRelations() as $relation) {
                $query = $model->{$relation}();

                $children = $query->get();
                $query->update($this->catalogParentDeleteMarker($model));
                $children->each(fn (Model $child) => $child->delete());
            }

            $model->delete();
        });
    }

    private function restoreCatalogChildren(Model $model): void
    {
        foreach ($this->trashCascadeRelations() as $relation) {
            $model->{$relation}()
                ->withTrashed()
                ->onlyTrashed()
                ->where($this->catalogParentDeleteMarker($model))
                ->get()
                ->each(function (Model $child): void {
                    $child->restore();
                    $child->forceFill($this->emptyCatalogParentDeleteMarker())->save();
                });
        }
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

    private function queueForceDelete(Model $model): void
    {
        if (! CatalogPurgeStatus::canQueue($model->getAttribute('purge_status'))) {
            return;
        }

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

        ForceDeleteCatalogItemJob::dispatch(
            $model::class,
            $model->getKey(),
            $this->trashCascadeRelations(),
        )->afterCommit();
    }

    /**
     * @return array<string, int|string>
     */
    private function catalogParentDeleteMarker(Model $model): array
    {
        return [
            'deleted_by_catalog_parent_type' => $model->getMorphClass(),
            'deleted_by_catalog_parent_id' => $model->getKey(),
        ];
    }

    /**
     * @return array<string, null>
     */
    private function emptyCatalogParentDeleteMarker(): array
    {
        return [
            'deleted_by_catalog_parent_type' => null,
            'deleted_by_catalog_parent_id' => null,
        ];
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
