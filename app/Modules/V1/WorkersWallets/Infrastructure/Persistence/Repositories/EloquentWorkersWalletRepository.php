<?php

namespace App\Modules\V1\WorkersWallets\Infrastructure\Persistence\Repositories;

use App\Modules\V1\WorkersWallets\Domain\Models\WorkersWallet;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WorkersWalletRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentWorkersWalletRepository implements WorkersWalletRepositoryInterface
{

    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)
            ->paginate(request('per_page', 15));
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?WorkersWallet
    {
        return $this->query($relations, $relationsCount)
            ->where('id', $id)
            ->first();
    }

    public function create(array $attributes): WorkersWallet
    {
        return DB::transaction(function () use ($attributes) {
            $workersWallet = WorkersWallet::create($attributes);

            return $workersWallet;
        });
    }

    public function update(WorkersWallet $workersWallet, array $attributes): WorkersWallet
    {
        return DB::transaction(function () use ($workersWallet, $attributes) {
            $workersWallet->update($attributes);

            return $workersWallet;
        });
    }

    public function delete(WorkersWallet $workersWallet): void
    {
        $workersWallet->delete();
    }

    private function query(array $relations = [], array $relationsCount = [], array $filters = []): Builder
    {
        return WorkersWallet::query()
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }
}
