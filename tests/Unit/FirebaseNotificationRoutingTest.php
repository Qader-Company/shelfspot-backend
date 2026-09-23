<?php

namespace Tests\Unit;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Notifications\Channels\DeduplicatedBroadcastChannel;
use App\Notifications\Channels\DeduplicatedDatabaseChannel;
use App\Notifications\Channels\FirebaseChannel;
use App\Notifications\RealtimeNotification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FirebaseNotificationRoutingTest extends TestCase
{
    #[DataProvider('workerFirebaseEvents')]
    public function test_firebase_is_used_only_for_allowed_worker_events(string $event): void
    {
        config()->set('notifications.firebase.enabled', true);

        $notification = new RealtimeNotification(['event' => $event]);

        $this->assertSame([
            DeduplicatedDatabaseChannel::class,
            DeduplicatedBroadcastChannel::class,
            FirebaseChannel::class,
        ], $notification->via($this->user(PortalTypeEnum::WORKER)));
    }

    public function test_non_worker_event_does_not_use_firebase_for_a_worker(): void
    {
        config()->set('notifications.firebase.enabled', true);

        $notification = new RealtimeNotification(['event' => 'task.completed']);

        $this->assertSame([
            DeduplicatedDatabaseChannel::class,
            DeduplicatedBroadcastChannel::class,
        ], $notification->via($this->user(PortalTypeEnum::WORKER)));
    }

    public function test_allowed_worker_event_does_not_use_firebase_for_other_portals(): void
    {
        config()->set('notifications.firebase.enabled', true);

        $notification = new RealtimeNotification(['event' => 'task.reassigned']);

        foreach ([PortalTypeEnum::COMPANY, PortalTypeEnum::ADMIN] as $portal) {
            $this->assertSame([
                DeduplicatedDatabaseChannel::class,
                DeduplicatedBroadcastChannel::class,
            ], $notification->via($this->user($portal)));
        }
    }

    public static function workerFirebaseEvents(): array
    {
        return [
            'published task' => ['task.published'],
            'reassigned task' => ['task.reassigned'],
            'reopened task' => ['task.reopened'],
        ];
    }

    private function user(PortalTypeEnum $type): User
    {
        $user = new User;
        $user->forceFill(['id' => 7, 'type' => $type]);

        return $user;
    }
}
