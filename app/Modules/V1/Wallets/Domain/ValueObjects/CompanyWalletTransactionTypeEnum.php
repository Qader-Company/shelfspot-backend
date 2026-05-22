<?php

namespace App\Modules\V1\Wallets\Domain\ValueObjects;

enum CompanyWalletTransactionTypeEnum: string
{
    case COUPON_REDEMPTION = 'coupon_redemption';
    case ADMIN_GRANT = 'admin_grant';
    case TASK_PAYMENT = 'task_payment';
    case TASK_REFUND = 'task_refund';
    case ADJUSTMENT = 'adjustment';
}
