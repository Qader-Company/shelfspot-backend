<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskServiceStatusEnum: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return __("enums.task_service_status.{$this->value}");
    }
}
