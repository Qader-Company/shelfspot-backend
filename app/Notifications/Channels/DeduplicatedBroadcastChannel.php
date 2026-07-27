<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class DeduplicatedBroadcastChannel extends BroadcastChannel
{
    public function send($notifiable, Notification $notification): mixed
    {
        if (! method_exists($notification, 'dedupeKey') || $notification->dedupeKey($notifiable) === null) {
            return parent::send($notifiable, $notification);
        }

        $now = now();
        $inserted = DB::table('notification_deliveries')->insertOrIgnore([
            'dedupe_key' => $notification->dedupeKey($notifiable),
            'channel' => 'broadcast',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $inserted === 1 ? parent::send($notifiable, $notification) : null;
    }
}
