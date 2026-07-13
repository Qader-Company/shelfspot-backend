<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskWorkerAssignmentTypeEnum: string
{
    case INITIAL = 'initial';
    case REOPENED_SAME_WORKER = 'reopened_same_worker';
    case REOPENED_REASSIGNED = 'reopened_reassigned';
    case REASSIGNED = 'reassigned';

    public function isPriority(): bool
    {
        return $this !== self::INITIAL;
    }
}
