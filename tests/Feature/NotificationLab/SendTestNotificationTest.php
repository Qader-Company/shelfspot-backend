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
            'event' => 'lab.test',
            'category' => 'test',
            'priority' => 'normal',
            'title' => 'Lab test',
            'message' => 'Testing all selected portals.',
            'meta' => ['scenario' => 'feature-test'],
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('data.queued', 2)
            ->assertJsonPath('data.recipients.0.portal', 'admin')
            ->assertJsonPath('data.recipients.1.portal', 'worker');

        Notification::assertSentTo($admin, RealtimeNotification::class);
        Notification::assertSentTo($worker, RealtimeNotification::class);
    }

    public function test_it_rejects_a_user_from_the_wrong_portal(): void
    {
        Notification::fake();
        $worker = $this->user(PortalTypeEnum::WORKER, 'worker@example.test');

        $this->postJson('/notification-lab/send', [
            'targets' => [['portal' => 'admin', 'user_id' => $worker->id]],
            'event' => 'lab.test',
            'category' => 'test',
            'priority' => 'normal',
            'title' => 'Lab test',
            'message' => 'This should not be sent.',
        ])->assertUnprocessable()->assertJsonValidationErrors('targets.0.portal');

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
