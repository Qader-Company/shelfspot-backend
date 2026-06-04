<?php

namespace App\Modules\Shared\Domain\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;

interface TrashableRepositoryInterface
{
    public function getTrash(array $filters = []): LengthAwarePaginator;

    public function bulkDelete(array $ids): int;

    public function restore(int $id): bool;

    public function bulkRestore(array $ids): int;

    public function forceDelete(int $id): bool;

    public function bulkForceDelete(array $ids): int;
}
