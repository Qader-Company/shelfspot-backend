<?php

namespace App\Modules\V1\Tasks\Domain\Repositories;

use App\Modules\V1\Tasks\Domain\Models\Task;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Task;

    public function create(array $attributes): Task;

    public function update(Task $task, array $attributes): Task;

    public function delete(Task $task): void;

    public function TasksByCoordinates(float $latitude, float $longitude, float $radiusKilometers, array $boundingBox, array $filters = []): CursorPaginator;

    public function assignedToWorker(int $workerId, array $filters = [], array $relations = [], string $paginationType = 'cursor'): LengthAwarePaginator|CursorPaginator;
}
