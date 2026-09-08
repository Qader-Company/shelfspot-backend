<?php

namespace App\Modules\V1\Stores\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Infrastructure\Persistence\Repositories\HandlesTrash;
use App\Modules\V1\Stores\Domain\Models\Store;
use App\Modules\V1\Stores\Domain\Repositories\StoreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentStoreRepository implements StoreRepositoryInterface
{
    use HandlesTrash;

    protected function trashableModel(): string
    {
        return Store::class;
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return Store::query()
            ->when($filters, fn ($query) => $query->filter($filters))
            ->latest()
            ->paginate();
    }

    public function getById(int $id): ?Store
    {
        return Store::query()->find($id);
    }

    public function getActiveById(int $id): ?Store
    {
        return Store::query()
            ->where('is_active', true)
            ->find($id);
    }

    public function options(): Collection
    {
        return Store::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('number')
            ->get();
    }

    public function create(array $attributes): Store
    {
        return Store::query()->create($attributes);
    }

    public function update(Store $store, array $attributes): Store
    {
        $store->update($attributes);

        return $store->refresh();
    }

    public function delete(Store $store): void
    {
        $store->delete();
    }
}
