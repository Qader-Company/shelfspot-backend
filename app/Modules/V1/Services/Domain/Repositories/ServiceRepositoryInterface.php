<?php

namespace App\Modules\V1\Services\Domain\Repositories;

use App\Modules\V1\Services\Domain\Models\Service;
use Illuminate\Support\Collection;

interface ServiceRepositoryInterface
{
    public function getAll(
        array $relations = [],
        array $relationsCount = [],
        array $filters = [],
    ): Collection;

    public function getById(
        int $id,
        array $relations = [],
        array $relationsCount = [],
    ): ?Service;

    public function getByKey(
        string $key,
        array $relations = [],
        array $relationsCount = [],
    ): ?Service;

    public function update(Service $service, array $attributes): Service;
}
