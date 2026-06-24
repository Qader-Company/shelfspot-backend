<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case STARTED = 'started';
    case IN_PROGRESS = 'in_progress';
    case WORKER_CANCELLED = 'worker_cancelled';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case ACCEPTED = 'accepted';
    case REOPENED = 'reopened';
    case FAILED = 'failed';
}
