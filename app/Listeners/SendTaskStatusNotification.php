<?php

namespace App\Listeners;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskNotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskStatusNotification implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly TaskNotificationDispatcher $dispatcher) {}

    public function shouldQueue(TaskStatusUpdated $event): bool
    {
        return $event->notificationSnapshot !== null;
    }

    public function viaQueue(TaskStatusUpdated $event): string
    {
        return $event->notificationSnapshot?->priority === 'high'
            ? config('notifications.queues.high')
            : config('notifications.queues.normal');
    }

    public function backoff(): array
    {
        return config('notifications.backoff');
    }

    public function tries(): int
    {
        return config('notifications.tries');
    }

    public function handle(TaskStatusUpdated $event): void
    {
        if ($event->notificationSnapshot !== null) {
            $this->dispatcher->dispatch($event->notificationSnapshot);
        }
    }
}
