<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskWorkerAssignmentOutcomeEnum: string
{
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case WORKER_CANCELLED = 'worker_cancelled';
    case REASSIGNED = 'reassigned';
    case START_DEADLINE_EXPIRED = 'start_deadline_expired';
    case REOPEN_DEADLINE_EXPIRED = 'reopen_deadline_expired';
}
