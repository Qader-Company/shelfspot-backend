<?php

namespace App\Modules\Shared\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SoftDeleteCatalogItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param class-string<Model> $modelClass
     * @param array<int, string> $relations
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly int $modelId,
        private readonly array $relations,
    ) {
    }

    public function handle(): void
    {
        $model = $this->findModel();

        if (! $model) {
            return;
        }

        foreach ($this->relations as $relation) {
            $query = $model->{$relation}();

            $query->chunkById(100, function ($children) use ($model): void {
                $children->each(function (Model $child) use ($model): void {
                    $child->forceFill($this->catalogParentDeleteMarker($model))->save();
                    $child->delete();
                });
            });
        }

        $model->delete();
    }

    private function findModel(): ?Model
    {
        $modelClass = $this->modelClass;

        return $modelClass::query()->find($this->modelId);
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
}
