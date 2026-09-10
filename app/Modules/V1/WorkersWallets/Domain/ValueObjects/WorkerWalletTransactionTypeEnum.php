<?php

namespace App\Modules\V1\WorkersWallets\Domain\ValueObjects;

enum WorkerWalletTransactionTypeEnum: string
{
    case TASK_EARNING = 'task_earning';
    case WITHDRAWAL = 'withdrawal';
    case WITHDRAWAL_REFUND = 'withdrawal_refund';
    case ADJUSTMENT = 'adjustment';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __("enums.worker_wallet_transaction_type.{$this->value}");
    }

    public static function getTypes(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }
}
