<?php

namespace App\Notifications;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Notifications\Channels\DeduplicatedBroadcastChannel;
use App\Notifications\Channels\DeduplicatedDatabaseChannel;
use App\Notifications\Channels\FirebaseChannel;
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
        $channels = [DeduplicatedDatabaseChannel::class, DeduplicatedBroadcastChannel::class];

        if (config('notifications.firebase.enabled')
            && $notifiable instanceof User
            && $notifiable->type === PortalTypeEnum::WORKER) {
            $channels[] = FirebaseChannel::class;
        }

        return $channels;
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
            FirebaseChannel::class => $notificationQueue,
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, string>, priority: string}
     */
    public function toFirebase(object $notifiable): array
    {
        $data = [];

        foreach (['event', 'category', 'priority', 'task_id', 'company_id', 'status', 'actor_id', 'occurred_at'] as $key) {
            if (array_key_exists($key, $this->payload)) {
                $data[$key] = $this->stringifyFirebaseValue($this->payload[$key]);
            }
        }

        if (isset($this->payload['action']) && is_array($this->payload['action'])) {
            $data['action_resource'] = (string) ($this->payload['action']['resource'] ?? '');
            $data['action_id'] = (string) ($this->payload['action']['id'] ?? '');
        }

        if (array_key_exists('meta', $this->payload)) {
            $data['meta'] = $this->stringifyFirebaseValue($this->payload['meta']);
        }

        if (is_string($this->id) && $this->id !== '') {
            $data['notification_id'] = $this->id;
        }

        return [
            'title' => (string) ($this->payload['title'] ?? config('app.name')),
            'body' => (string) ($this->payload['description'] ?? ''),
            'data' => $data,
            'priority' => ($this->payload['priority'] ?? 'normal') === 'high' ? 'high' : 'normal',
        ];
    }

    public function backoff(): array
    {
        return config('notifications.backoff');
    }

    private function stringifyFirebaseValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
