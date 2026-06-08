<?php

namespace App\Modules\V1\Workers\Domain\Repositories;

use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkerRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Worker;

    public function findByUserId(int $userId, array $relations = []): ?Worker;

    public function create(array $attributes): Worker;

    public function update(Worker $worker, array $attributes): Worker;

    public function delete(Worker $worker): void;
}
