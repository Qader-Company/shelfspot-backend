<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case WORKER_CANCELLED = 'worker_cancelled';
    case COMPANY_DELETED = 'company_deleted';
    case ADMIN_DELETED = 'admin_deleted';
}
