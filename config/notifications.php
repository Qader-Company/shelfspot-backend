<?php

return [
    'queues' => [
        'high' => env('NOTIFICATIONS_HIGH_QUEUE', 'notifications-high'),
        'normal' => env('NOTIFICATIONS_NORMAL_QUEUE', 'notifications-normal'),
        'broadcasts' => env('NOTIFICATIONS_BROADCAST_QUEUE', 'broadcasts'),
    ],

    'tries' => (int) env('NOTIFICATIONS_QUEUE_TRIES', 3),

    'backoff' => [30, 120, 600],

    'firebase' => [
        'enabled' => env('FIREBASE_NOTIFICATIONS_ENABLED', false),
        'android_channel_id' => env('FIREBASE_ANDROID_CHANNEL_ID', 'shelfspot_notifications'),
        'worker_events' => [
            'task.published',
            'task.reassigned',
            'task.reopened',
        ],
    ],

    'health' => [
        'max_pending_per_queue' => (int) env('NOTIFICATIONS_QUEUE_MAX_PENDING', 100),
        'reverb_timeout_seconds' => (int) env('NOTIFICATIONS_REVERB_HEALTH_TIMEOUT', 2),
    ],

    'retention' => [
        'read_days' => (int) env('NOTIFICATIONS_READ_RETENTION_DAYS', 30),
        'unread_days' => (int) env('NOTIFICATIONS_UNREAD_RETENTION_DAYS', 90),
        'delivery_days' => (int) env('NOTIFICATIONS_DELIVERY_RETENTION_DAYS', 90),
    ],
];
