<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskPaymentStatusEnum: string
{
    case PENDING = 'pending';
    case CHARGED = 'charged';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';

    public function label(): string
    {
        return __("enums.task_payment_status.{$this->value}");
    }
}
