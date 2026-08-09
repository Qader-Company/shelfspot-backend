<?php

namespace App\Modules\V1\Tasks\Domain\Repositories;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Task;
    public function getByIdAndLockedForUpdate(int $id, array $relations = [], array $relationsCount = []): ?Task;

    public function create(array $attributes): Task;

    public function update(Task $task, array $attributes): Task;

    public function delete(Task $task): void;

    public function getCompanyTrash(int $companyId, array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getCompanyDeletedForAdmin(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getCompanyDeletedById(int $id, array $relations = [], bool $includePurged = false): ?Task;

    public function restoreCompanyDeleted(Task $task): Task;

    public function purgeForCompany(Task $task): Task;

    public function forceDeleteCompanyDeleted(Task $task): void;

    public function tasksByCoordinates(float $latitude, float $longitude, float $radiusKilometers, array $boundingBox, array $filters = []): CursorPaginator;

    public function assignedToWorker(int $workerId, array $filters = [], array $relations = [], array $relationsCount = [], string $paginationType = 'cursor'): LengthAwarePaginator|CursorPaginator;

    public function assignedTaskForWorker(int $taskId, int $workerId, array $relations = []): ?Task;

    public function assignedToWorkerForAdmin(int $workerId, array $filters = [], array $relations = []): Collection;

    public function countAssignedToWorkerByStatus(int $workerId, TaskStatusEnum $status): int;

    public function inProgressAssignedToWorker(int $workerId, array $relations = []): ?Task;

    public function latestForCompany(int $companyId, int $limit = 15, array $relations = []): Collection;

    public function countForCompany(int $companyId, ?TaskStatusEnum $status = null): int;

    public function sumTotalPriceForCompany(int $companyId, ?TaskPaymentStatusEnum $paymentStatus = null): float;

    public function listRelations(): array;

    public function listRelationsCount(): array;

    public function detailRelations(): array;

    public function relations(): array;

}
