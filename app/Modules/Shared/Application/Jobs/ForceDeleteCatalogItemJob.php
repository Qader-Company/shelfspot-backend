<?php

namespace App\Modules\Shared\Application\Jobs;

use App\Modules\Shared\Domain\ValueObjects\CatalogPurgeStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ForceDeleteCatalogItemJob implements ShouldQueue
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
                $models->each(fn (Model $model): mixed => $this->forceDeleteModelWithChildren($model));
            });
    }

    private function forceDeleteModelWithChildren(Model $model): void
    {
        try {
            foreach ($this->relations as $relation) {
                $model->{$relation}()
                    ->withTrashed()
                    ->chunkById(100, fn ($children) => $children->each(
                        fn (Model $child) => $child->forceDelete()
                    ));
            }

            $model->forceDelete();
        } catch (Throwable $exception) {
            $this->markFailed($model, $exception);

            throw $exception;
        }
    }

    private function modelQuery(): Builder
    {
        $modelClass = $this->modelClass;

        return $modelClass::query();
    }

    private function markFailed(Model $model, Throwable $exception): void
    {
        $model->forceFill([
            'purge_status' => CatalogPurgeStatus::FAILED,
            'purge_failure_reason' => $exception->getMessage(),
        ])->save();
    }
}
