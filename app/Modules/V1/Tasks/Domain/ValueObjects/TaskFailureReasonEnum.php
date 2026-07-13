<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskFailureReasonEnum: string
{
    case REOPEN_DEADLINE_EXPIRED = 'reopen_deadline_expired';
}
