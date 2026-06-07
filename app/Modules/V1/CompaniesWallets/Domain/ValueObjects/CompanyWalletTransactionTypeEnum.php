<?php

namespace App\Modules\V1\CompaniesWallets\Domain\ValueObjects;

enum CompanyWalletTransactionTypeEnum: string
{
    case COUPON_REDEMPTION = 'coupon_redemption';
    case ADMIN_GRANT = 'admin_grant';
    case TASK_PAYMENT = 'task_payment';
    case TASK_REFUND = 'task_refund';
    case ADJUSTMENT = 'adjustment';

    public static function values() : array
    {
        return array_map(
            fn(self $item) => $item->value,
            self::cases()
        );
    }

    public function label() : string
    {
        return __("company.wallet.{$this->value}");
    }

    public static function getTypes() : array
    {
        return array_map(
            fn(self $item) => [
                'value' => $item->value,
                'label' => $item->label(),
            ], self::cases()
        );
    }
}
