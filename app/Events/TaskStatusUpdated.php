<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $fromStatus;
    public $task;
    public $toStatus;
    public $worker;
    public $meta;
    /**
     * Create a new event instance.
     */
    public function __construct($task, $fromStatus, $toStatus, $worker, $meta)
    {
        $this->task = $task;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        $this->worker = $worker;
        $this->meta = $meta;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
