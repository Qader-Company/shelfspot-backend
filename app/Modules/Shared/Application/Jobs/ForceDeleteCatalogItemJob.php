<?php

namespace App\Modules\Shared\Application\Jobs;

use App\Modules\Shared\Domain\ValueObjects\CatalogPurgeStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ForceDeleteCatalogItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

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
            $this->markFailed($exception);

            throw $exception;
        }
    }

    private function findModel(): ?Model
    {
        $modelClass = $this->modelClass;

        return $modelClass::query()
            ->onlyTrashed()
            ->find($this->modelId);
    }

    private function markFailed(Throwable $exception): void
    {
        $this->findModel()?->forceFill([
            'purge_status' => CatalogPurgeStatus::FAILED,
            'purge_failure_reason' => $exception->getMessage(),
        ])->save();
    }
}
