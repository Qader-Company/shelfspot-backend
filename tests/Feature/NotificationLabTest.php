<?php

namespace Tests\Feature;

use App\Modules\V1\Users\Domain\Models\User;
use Tests\TestCase;

class NotificationLabTest extends TestCase
{
    public function test_notification_lab_page_is_available(): void
    {
        $this->get('/notification-lab')
            ->assertOk()
            ->assertSee('Notification Lab');
    }

    public function test_broadcast_auth_requires_sanctum_authentication(): void
    {
        $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-App.Models.User.1',
            'socket_id' => '1234.5678',
        ])->assertUnauthorized();
    }

    public function test_users_broadcast_notifications_on_the_documented_private_channel(): void
    {
        $user = new User;
        $user->setAttribute('id', 42);

        $this->assertSame('App.Models.User.42', $user->receivesBroadcastNotificationsOn());
    }
}
