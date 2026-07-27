<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

class DeduplicatedDatabaseChannel extends DatabaseChannel
{
    public function send($notifiable, Notification $notification): mixed
    {
        if (! method_exists($notification, 'dedupeKey') || $notification->dedupeKey($notifiable) === null) {
            return parent::send($notifiable, $notification);
        }

        $relation = $notifiable->routeNotificationFor('database', $notification);
        $databaseNotification = $relation->getRelated()->newInstance($this->buildPayload($notifiable, $notification));
        $databaseNotification->setAttribute('notifiable_type', $notifiable->getMorphClass());
        $databaseNotification->setAttribute('notifiable_id', $notifiable->getKey());
        $databaseNotification->setAttribute('dedupe_key', $notification->dedupeKey($notifiable));
        $databaseNotification->setCreatedAt(now());
        $databaseNotification->setUpdatedAt(now());

        $inserted = $databaseNotification->newQuery()->insertOrIgnore($databaseNotification->getAttributes());

        return $inserted === 1 ? $databaseNotification : null;
    }
}
