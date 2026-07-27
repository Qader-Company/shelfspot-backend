<?php

namespace Tests\Unit;

use App\Events\TaskStatusUpdated;
use App\Listeners\SendTaskStatusNotification;
use App\Modules\V1\Tasks\Application\Data\TaskStatusNotificationSnapshot;
use App\Modules\V1\Tasks\Application\Services\TaskNotificationDispatcher;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Notifications\Channels\DeduplicatedBroadcastChannel;
use App\Notifications\Channels\DeduplicatedDatabaseChannel;
use App\Notifications\RealtimeNotification;
use Tests\TestCase;

class NotificationQueueConfigurationTest extends TestCase
{
    public function test_high_priority_task_notifications_use_the_high_priority_queue(): void
    {
        $event = $this->eventWithSnapshot('high');
        $listener = new SendTaskStatusNotification(app(TaskNotificationDispatcher::class));

        $this->assertTrue($listener->shouldQueue($event));
        $this->assertSame(config('notifications.queues.high'), $listener->viaQueue($event));
        $this->assertSame(config('notifications.tries'), $listener->tries());
        $this->assertSame(config('notifications.backoff'), $listener->backoff());
    }

    public function test_normal_database_delivery_and_broadcast_use_separate_queues(): void
    {
        $notification = new RealtimeNotification([
            'event' => 'task.published',
            'priority' => 'normal',
        ]);

        $this->assertSame(config('notifications.tries'), $notification->tries);
        $this->assertSame(config('notifications.backoff'), $notification->backoff());
        $this->assertSame([
            DeduplicatedDatabaseChannel::class => config('notifications.queues.normal'),
            DeduplicatedBroadcastChannel::class => config('notifications.queues.broadcasts'),
        ], $notification->viaQueues());
        $this->assertSame(config('notifications.queues.broadcasts'), $notification->toBroadcast(new User)->queue);
    }

    public function test_notification_health_command_passes_when_no_services_are_enabled(): void
    {
        config([
            'queue.default' => 'sync',
            'broadcasting.default' => 'null',
        ]);

        $this->artisan('notifications:health')
            ->expectsOutput('Notification delivery health check passed.')
            ->assertExitCode(0);
    }

    private function eventWithSnapshot(string $priority): TaskStatusUpdated
    {
        $event = new TaskStatusUpdated(
            task: new Task,
            fromStatus: TaskStatusEnum::DRAFT,
            toStatus: TaskStatusEnum::PENDING,
            worker: null,
            meta: [],
        );
        $event->notificationSnapshot = new TaskStatusNotificationSnapshot(
            taskId: 1,
            companyId: 1,
            event: 'task.published',
            priority: $priority,
            fromStatus: TaskStatusEnum::DRAFT->value,
            toStatus: TaskStatusEnum::PENDING->value,
            actorId: null,
            recipientIds: [1],
            statusHistoryId: 1,
            meta: [],
            occurredAt: now()->toIso8601String(),
        );

        return $event;
    }
}
