<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_expired_notifications_and_delivery_records(): void
    {
        $now = now();

        $expiredReadId = $this->insertNotification($now->copy()->subDays(40), $now->copy()->subDays(31));
        $recentlyReadId = $this->insertNotification($now->copy()->subDays(40), $now->copy()->subDays(29));
        $expiredUnreadId = $this->insertNotification($now->copy()->subDays(91));
        $recentUnreadId = $this->insertNotification($now->copy()->subDays(89));

        DB::table('notification_deliveries')->insert([
            [
                'dedupe_key' => 'expired-delivery',
                'channel' => 'broadcast',
                'created_at' => $now->copy()->subDays(91),
                'updated_at' => $now->copy()->subDays(91),
            ],
            [
                'dedupe_key' => 'recent-delivery',
                'channel' => 'broadcast',
                'created_at' => $now->copy()->subDays(89),
                'updated_at' => $now->copy()->subDays(89),
            ],
        ]);

        $this->artisan('notifications:prune')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['id' => $expiredReadId]);
        $this->assertDatabaseHas('notifications', ['id' => $recentlyReadId]);
        $this->assertDatabaseMissing('notifications', ['id' => $expiredUnreadId]);
        $this->assertDatabaseHas('notifications', ['id' => $recentUnreadId]);
        $this->assertDatabaseMissing('notification_deliveries', ['dedupe_key' => 'expired-delivery']);
        $this->assertDatabaseHas('notification_deliveries', ['dedupe_key' => 'recent-delivery']);
    }

    public function test_dry_run_does_not_delete_anything(): void
    {
        $notificationId = $this->insertNotification(now()->subDays(100));

        $this->artisan('notifications:prune --dry-run')
            ->expectsOutputToContain('Would delete 1 unread notifications.')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['id' => $notificationId]);
    }

    private function insertNotification(mixed $createdAt, mixed $readAt = null): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'test',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'data' => '{}',
            'dedupe_key' => 'test-'.$id,
            'read_at' => $readAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $id;
    }
}
