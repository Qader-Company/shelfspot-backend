<?php

namespace App\Modules\V1\WorkersWallets\Domain\ValueObjects;

enum WithdrawalMethodEnum: string
{
    case BANK_ACCOUNT = 'bank_account';
    case WALLET = 'wallet';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __("enums.withdrawal_method.{$this->value}");
    }

    public static function getMethods(): array
    {
        return array_map(
            fn (self $method) => [
                'value' => $method->value,
                'label' => $method->label(),
            ],
            self::cases(),
        );
    }
}
