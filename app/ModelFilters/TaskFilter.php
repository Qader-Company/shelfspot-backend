<?php

namespace App\ModelFilters;

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

    public function paymentStatus($payment_status)
    {
        return $this->where('payment_status', $payment_status);
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
