<?php

namespace App\Listeners;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskNotificationDispatcher;

class CaptureTaskStatusNotificationSnapshot
{
    public function __construct(private readonly TaskNotificationDispatcher $dispatcher) {}

    public function handle(TaskStatusUpdated $event): void
    {
        if ($event->statusHistoryId === null) {
            throw new \RuntimeException("Task status history was not recorded for event [{$event->eventId}].");
        }

        $event->notificationSnapshot = $this->dispatcher->capture(
            task: $event->task,
            fromStatus: $event->fromStatus,
            toStatus: $event->toStatus,
            actor: $event->worker,
            statusHistoryId: $event->statusHistoryId,
            meta: $event->meta,
            occurredAt: $event->occurredAt,
        );
    }
}
