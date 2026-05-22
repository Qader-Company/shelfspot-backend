<?php

namespace App\Modules\V1\Wallets\Domain\ValueObjects;

enum WorkerWalletTransactionTypeEnum: string
{
    case TASK_EARNING = 'task_earning';
    case WITHDRAWAL = 'withdrawal';
    case ADJUSTMENT = 'adjustment';
}
