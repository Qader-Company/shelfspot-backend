<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskPaymentStatusEnum: string
{
    case PENDING = 'pending';
    case CHARGED = 'charged';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';

    public static function values(): array
    {
        return array_map(
            fn (self $status) => $status->value,
            self::cases()
        );
    }

    public function label(): string
    {
        return __("enums.task_payment_status.{$this->value}");
    }
}
