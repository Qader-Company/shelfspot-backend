<?php

namespace App\Modules\V1\Tasks\Application\Data;

final readonly class TaskStatusNotificationSnapshot
{
    public function __construct(
        public int $taskId,
        public int $companyId,
        public string $event,
        public string $priority,
        public string $fromStatus,
        public string $toStatus,
        public ?int $actorId,
        public array $recipientIds,
        public int $statusHistoryId,
        public array $meta,
        public string $occurredAt,
    ) {}
}
