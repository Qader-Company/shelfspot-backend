<?php

namespace Tests\Unit;

use App\Modules\V1\Users\Domain\Models\User;
use App\Notifications\RealtimeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeNotificationDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_retried_notification_key_creates_one_database_notification(): void
    {
        $user = User::factory()->create();
        $payload = [
            'event' => 'task.completed',
            'category' => 'task',
            'priority' => 'high',
            'task_id' => 10,
            'company_id' => 1,
            'status' => 'completed',
            'actor_id' => 2,
            'action' => ['resource' => 'task', 'id' => 10],
            'meta' => ['status_history_id' => 77],
            'occurred_at' => now()->toIso8601String(),
        ];
        $notificationKey = 'task-status:77:task.completed:recipient:'.$user->id;

        $user->notify(new RealtimeNotification($payload, $notificationKey));
        $user->notify(new RealtimeNotification($payload, $notificationKey));

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['dedupe_key' => $notificationKey]);
        $this->assertDatabaseCount('notification_deliveries', 1);
        $this->assertDatabaseHas('notification_deliveries', [
            'dedupe_key' => $notificationKey,
            'channel' => 'broadcast',
        ]);
    }
}
