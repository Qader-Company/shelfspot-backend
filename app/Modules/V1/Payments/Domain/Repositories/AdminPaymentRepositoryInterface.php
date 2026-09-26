<?php

namespace App\Modules\V1\Payments\Domain\Repositories;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminPaymentRepositoryInterface
{
    public function paginate(array $filters, ?TaskPaymentStatusEnum $paymentStatus): LengthAwarePaginator;

    public function find(int $id): Task;

    public function summary(array $filters): array;
}
