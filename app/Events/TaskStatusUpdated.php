<?php

namespace App\Events;

use App\Modules\V1\Tasks\Application\Data\TaskStatusNotificationSnapshot;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TaskStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fromStatus;

    public $task;

    public $toStatus;

    public $worker;

    public $meta;

    public readonly string $eventId;

    public readonly string $occurredAt;

    public ?int $statusHistoryId = null;

    public ?TaskStatusNotificationSnapshot $notificationSnapshot = null;

    /**
     * Create a new event instance.
     */
    public function __construct($task, $fromStatus, $toStatus, $worker, $meta, ?string $eventId = null)
    {
        $this->task = $task;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        $this->worker = $worker;
        $this->meta = $meta;
        $this->eventId = $eventId ?? (string) Str::uuid();
        $this->occurredAt = now()->toIso8601String();
    }
}
