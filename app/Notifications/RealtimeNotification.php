<?php

namespace App\Notifications;

use App\Notifications\Channels\DeduplicatedBroadcastChannel;
use App\Notifications\Channels\DeduplicatedDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class RealtimeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public function __construct(
        private readonly array $payload,
        private readonly ?string $notificationKey = null,
    ) {
        $this->tries = config('notifications.tries');
    }

    public function via(object $notifiable): array
    {
        return [DeduplicatedDatabaseChannel::class, DeduplicatedBroadcastChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->payload))
            ->onQueue(config('notifications.queues.broadcasts'));
    }

    public function broadcastType(): string
    {
        return 'shelfspot.notification.'.$this->payload['event'];
    }

    public function dedupeKey(object $notifiable): ?string
    {
        return $this->notificationKey;
    }

    public function viaQueues(): array
    {
        $notificationQueue = ($this->payload['priority'] ?? 'normal') === 'high'
            ? config('notifications.queues.high')
            : config('notifications.queues.normal');

        return [
            DeduplicatedDatabaseChannel::class => $notificationQueue,
            DeduplicatedBroadcastChannel::class => config('notifications.queues.broadcasts'),
        ];
    }

    public function backoff(): array
    {
        return config('notifications.backoff');
    }
}
