<?php

namespace App\ModelFilters;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use EloquentFilter\ModelFilter;

class TaskFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function companyStatus($status)
    {
        if ($status === TaskStatusEnum::IN_PROGRESS->value) {
            return $this->where(function ($query) {
                $query->whereIn('status', [
                    TaskStatusEnum::IN_PROGRESS->value,
                    TaskStatusEnum::WORKER_CANCELLED->value,
                    TaskStatusEnum::REASSIGNED->value,
                ])->orWhere(function ($query) {
                    $query->where('status', TaskStatusEnum::STARTED->value)
                        ->whereHas('currentWorkerAssignment', fn ($assignment) => $assignment->where(
                            'assignment_type',
                            TaskWorkerAssignmentTypeEnum::REASSIGNED->value,
                        ));
                });
            });
        }

        if ($status === TaskStatusEnum::STARTED->value) {
            return $this->where('status', TaskStatusEnum::STARTED->value)
                ->whereDoesntHave('currentWorkerAssignment', fn ($assignment) => $assignment->where(
                    'assignment_type',
                    TaskWorkerAssignmentTypeEnum::REASSIGNED->value,
                ));
        }

        return $this->where('status', $status);
    }

    public function paymentStatus($payment_status)
    {
        return $this->where('payment_status', $payment_status);
    }

    public function storeId($storeId)
    {
        return $this->where('store_id', $storeId);
    }

    public function companyId($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function assignedWorkerId($workerId)
    {
        return $this->where('assigned_worker_id', $workerId);
    }

    public function createdBy($userId)
    {
        return $this->where('created_by', $userId);
    }

    public function dateFrom($date_from)
    {
        return $this->whereDate('date', '>=', $date_from);
    }

    public function dateTo($date_to)
    {
        return $this->whereDate('date', '<=', $date_to);
    }

    public function executionDate($executionDate)
    {
        return $this->whereDate('date', $executionDate);
    }
}
