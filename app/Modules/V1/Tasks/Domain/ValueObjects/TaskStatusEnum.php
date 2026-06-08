<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskStatusEnum: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ACCEPTED = 'accepted';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case DECLINED = 'declined';
}
