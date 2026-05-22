<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACCEPTED = 'accepted';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case FAILED_UNASSIGNED = 'failed_unassigned';
    case DECLINED = 'declined';
    case AWAITING_REASSIGNMENT = 'awaiting_reassignment';
}
