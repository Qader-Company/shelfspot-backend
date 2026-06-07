<?php

namespace App\Modules\V1\WorkersWallets\Domain\Repositories;

use App\Modules\V1\WorkersWallets\Domain\Models\WorkersWallet;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkersWalletRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?WorkersWallet;

    public function create(array $attributes): WorkersWallet;

    public function update(WorkersWallet $workersWallet, array $attributes): WorkersWallet;

    public function delete(WorkersWallet $workersWallet): void;
}
