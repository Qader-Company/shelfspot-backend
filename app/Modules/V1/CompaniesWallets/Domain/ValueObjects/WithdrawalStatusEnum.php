<?php

namespace App\Modules\V1\CompaniesWallets\Domain\ValueObjects;

enum WithdrawalStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PAID = 'paid';
}
