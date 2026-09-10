<?php

namespace App\Modules\V1\WorkersWallets\Domain\ValueObjects;

enum WithdrawalStatusEnum: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case REJECTED = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __("enums.withdrawal_status.{$this->value}");
    }

    public static function getStatuses(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
