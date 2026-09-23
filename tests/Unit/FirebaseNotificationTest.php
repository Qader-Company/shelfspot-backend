<?php

namespace Tests\Unit;

use App\Modules\Shared\Application\Firebase\FirebaseService;
use App\Modules\V1\Users\Domain\Models\DeviceToken;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Notifications\Channels\DeduplicatedBroadcastChannel;
use App\Notifications\Channels\DeduplicatedDatabaseChannel;
use App\Notifications\Channels\FirebaseChannel;
use App\Notifications\RealtimeNotification;
use Illuminate\Database\Eloquent\Collection;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use Tests\TestCase;

class FirebaseNotificationTest extends TestCase
{
    public function test_firebase_channel_is_only_added_for_worker_notifications(): void
    {
        config()->set('notifications.firebase.enabled', true);

        $notification = new RealtimeNotification($this->payload());
        $worker = $this->user(PortalTypeEnum::WORKER);
        $company = $this->user(PortalTypeEnum::COMPANY);

        $this->assertSame([
            DeduplicatedDatabaseChannel::class,
            DeduplicatedBroadcastChannel::class,
            FirebaseChannel::class,
        ], $notification->via($worker));

        $this->assertSame([
            DeduplicatedDatabaseChannel::class,
            DeduplicatedBroadcastChannel::class,
        ], $notification->via($company));
    }

    public function test_worker_firebase_channel_can_be_disabled_by_configuration(): void
    {
        config()->set('notifications.firebase.enabled', false);

        $notification = new RealtimeNotification($this->payload());

        $this->assertSame([
            DeduplicatedDatabaseChannel::class,
            DeduplicatedBroadcastChannel::class,
        ], $notification->via($this->user(PortalTypeEnum::WORKER)));
    }

    public function test_firebase_payload_contains_only_string_data_values(): void
    {
        $notification = new RealtimeNotification($this->payload());
        $notification->id = 'notification-uuid';

        $message = $notification->toFirebase($this->user(PortalTypeEnum::WORKER));

        $this->assertSame('Task assigned to you', $message['title']);
        $this->assertSame('Task #42 has been assigned to you.', $message['body']);
        $this->assertSame('42', $message['data']['task_id']);
        $this->assertSame('task', $message['data']['action_resource']);
        $this->assertSame('42', $message['data']['action_id']);
        $this->assertSame('notification-uuid', $message['data']['notification_id']);
        $this->assertSame('{"status_history_id":99}', $message['data']['meta']);

        foreach ($message['data'] as $value) {
            $this->assertIsString($value);
        }
    }

    public function test_channel_sends_to_all_registered_worker_devices(): void
    {
        config()->set('notifications.firebase.enabled', true);

        $worker = $this->user(PortalTypeEnum::WORKER);
        $worker->setRelation('deviceTokens', new Collection([
            new DeviceToken(['token' => 'token-one']),
            new DeviceToken(['token' => 'token-two']),
        ]));

        $report = MulticastSendReport::withItems([
            SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'token-one'), []),
            SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'token-two'), []),
        ]);

        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldReceive('sendToTokens')
            ->once()
            ->withArgs(function (array $tokens, string $title, string $body, array $data, string $priority): bool {
                return $tokens === ['token-one', 'token-two']
                    && $title === 'Task assigned to you'
                    && $body === 'Task #42 has been assigned to you.'
                    && $data['event'] === 'task.reassigned'
                    && $priority === 'high';
            })
            ->andReturn($report);

        $result = (new FirebaseChannel($firebase))->send(
            $worker,
            new RealtimeNotification($this->payload()),
        );

        $this->assertSame([
            'attempted' => 2,
            'successful' => 2,
            'failed' => 0,
            'pruned' => 0,
        ], $result);
    }

    private function user(PortalTypeEnum $type): User
    {
        $user = new User;
        $user->forceFill(['id' => 7, 'type' => $type]);

        return $user;
    }

    private function payload(): array
    {
        return [
            'event' => 'task.reassigned',
            'category' => 'task',
            'priority' => 'high',
            'title' => 'Task assigned to you',
            'description' => 'Task #42 has been assigned to you.',
            'task_id' => 42,
            'company_id' => 3,
            'status' => 'reassigned',
            'actor_id' => null,
            'action' => ['resource' => 'task', 'id' => 42],
            'meta' => ['status_history_id' => 99],
            'occurred_at' => '2026-09-23T10:00:00+00:00',
        ];
    }
}
