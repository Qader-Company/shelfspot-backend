<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

enum TaskStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case STARTED = 'started';
    case IN_PROGRESS = 'in_progress';
    case WORKER_CANCELLED = 'worker_cancelled';
    case COMPANY_CANCELLED = 'company_cancelled';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case ACCEPTED = 'accepted';
    case REOPENED = 'reopened';
    case FAILED = 'failed';

    public static function workerActiveStatuses(): array
    {
        return [
            self::STARTED,
            self::IN_PROGRESS,
            self::REOPENED,
        ];
    }

    public static function values(array $statuses): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            $statuses
        );
    }

    public function label(): string
    {
        return __("enums.task_status.{$this->value}");
    }

    public static function getStatuses(): array
    {
        return array_map(
            fn (self $item) => [
                'value' => $item->value,
                'label' => $item->label(),
            ], self::cases()
        );
    }
}
