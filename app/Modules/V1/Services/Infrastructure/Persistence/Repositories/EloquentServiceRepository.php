<?php

namespace App\Modules\V1\Services\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Support\Traits\HasTranslation;
use App\Modules\V1\Services\Domain\Models\Service;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Modules\V1\Services\Domain\Repositories\{ServiceRepositoryInterface};

class EloquentServiceRepository implements ServiceRepositoryInterface
{
    use HasTranslation;
    public function getAll(
        array $relations = [],
        array $relationsCount = [],
        array $filters = [],
    ): Collection
    {
        return $this->query(
            $relations,
            $relationsCount,
            $filters,
        )->get();
    }

    public function getByKey(string $key, array $relations = [], array $relationsCount = []): ?Service
    {
        return $this->query(
            relations: $relations,
            relationsCount: $relationsCount,
        )
        ->where('key', $key)
        ->first();
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Service
    {
        return $this->query(
            relations: $relations,
            relationsCount: $relationsCount,
        )->find($id);
    }

    public function update(Service $service, array $attributes): Service
    {
        return DB::transaction(function () use ($service, $attributes) {
            $translations = Arr::Pull($attributes, 'translations');
            $service->update($attributes);
            $this->fillTranslations($service, $translations);
            return $service;
        });
    }

    private function query(array $relations, array $relationsCount, array $filters = [])
    {
        return Service::when($filters, fn($q) => $q->filter($filters))
            ->when($relations, fn($q) => $q->with($relations))
            ->when($relationsCount, fn($q) => $q->withCount($relationsCount));
    }
}
