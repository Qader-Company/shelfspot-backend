<?php

namespace App\Modules\V1\Stores\Domain\Repositories;

use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\V1\Stores\Domain\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface StoreRepositoryInterface extends TrashableRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function getById(int $id): ?Store;

    public function getActiveById(int $id): ?Store;

    public function options(): Collection;

    public function create(array $attributes): Store;

    public function update(Store $store, array $attributes): Store;

    public function delete(Store $store): void;
}
