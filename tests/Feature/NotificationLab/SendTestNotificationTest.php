<?php

namespace Tests\Feature\NotificationLab;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Notifications\RealtimeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendTestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_a_test_notification_for_each_selected_portal(): void
    {
        Notification::fake();
        $admin = $this->user(PortalTypeEnum::ADMIN, 'admin@example.test');
        $worker = $this->user(PortalTypeEnum::WORKER, 'worker@example.test');

        $response = $this->postJson('/notification-lab/send', [
            'targets' => [
                ['portal' => 'admin', 'user_id' => $admin->id],
                ['portal' => 'worker', 'user_id' => $worker->id],
            ],
            'event' => 'task.completed',
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('data.queued', 2)
            ->assertJsonPath('data.recipients.0.portal', 'admin')
            ->assertJsonPath('data.recipients.1.portal', 'worker');

        Notification::assertSentTo($admin, RealtimeNotification::class, function (RealtimeNotification $notification) use ($admin) {
            $payload = $notification->toArray($admin);

            return $payload['event'] === 'task.completed'
                && $payload['category'] === 'task'
                && $payload['priority'] === 'high'
                && $payload['title'] === 'Task completed'
                && $payload['description'] === 'Task #1001 has been completed and is ready for review.'
                && $payload['task_id'] === 1001
                && $payload['action'] === ['resource' => 'task', 'id' => 1001]
                && $payload['meta']['is_test'] === true;
        });
        Notification::assertSentTo($worker, RealtimeNotification::class);
    }

    public function test_it_rejects_a_user_from_the_wrong_portal(): void
    {
        Notification::fake();
        $worker = $this->user(PortalTypeEnum::WORKER, 'worker@example.test');

        $this->postJson('/notification-lab/send', [
            'targets' => [['portal' => 'admin', 'user_id' => $worker->id]],
            'event' => 'task.completed',
        ])->assertUnprocessable()->assertJsonValidationErrors('targets.0.portal');

        Notification::assertNothingSent();
    }

    public function test_it_rejects_an_event_without_a_fixed_fixture(): void
    {
        Notification::fake();
        $admin = $this->user(PortalTypeEnum::ADMIN, 'admin@example.test');

        $this->postJson('/notification-lab/send', [
            'targets' => [['portal' => 'admin', 'user_id' => $admin->id]],
            'event' => 'custom.event',
        ])->assertUnprocessable()->assertJsonValidationErrors('event');

        Notification::assertNothingSent();
    }

    private function user(PortalTypeEnum $portal, string $email): User
    {
        return User::query()->create([
            'name' => ucfirst($portal->value).' tester',
            'email' => $email,
            'password' => 'password',
            'type' => $portal,
        ]);
    }
}
