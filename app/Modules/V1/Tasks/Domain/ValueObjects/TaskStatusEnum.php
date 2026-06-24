<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case STARTED = 'started';
    case IN_PROGRESS = 'in_progress';
    case WORKER_CANCELLED = 'worker_cancelled';
    case IN_REVIEW = 'completed';
    case FAILED = 'failed';
    case REJECTED = 'rejected';
    case ACCEPTED = 'accepted';
}
