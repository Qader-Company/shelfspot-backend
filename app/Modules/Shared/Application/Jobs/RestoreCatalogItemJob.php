<?php

namespace App\Modules\Shared\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RestoreCatalogItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param class-string<Model> $modelClass
     * @param array<int, int> $modelIds
     * @param array<int, string> $relations
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly array $modelIds,
        private readonly array $relations,
    ) {
    }

    public function handle(): void
    {
        $this->modelQuery()
            ->onlyTrashed()
            ->whereKey($this->modelIds)
            ->chunkById(100, function ($models): void {
                $models->each(fn (Model $model): mixed => $this->restoreModelWithChildren($model));
            });
    }

    private function restoreModelWithChildren(Model $model): void
    {
        $model->restore();

        foreach ($this->relations as $relation) {
            $model->{$relation}()
                ->withTrashed()
                ->onlyTrashed()
                ->where($this->catalogParentDeleteMarker($model))
                ->chunkById(100, function ($children): void {
                    $children->each(function (Model $child): void {
                        $child->restore();
                        $child->forceFill($this->emptyCatalogParentDeleteMarker())->save();
                    });
                });
        }
    }

    private function modelQuery(): Builder
    {
        $modelClass = $this->modelClass;

        return $modelClass::query();
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
}
