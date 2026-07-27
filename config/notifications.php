<?php

return [
    'queues' => [
        'high' => env('NOTIFICATIONS_HIGH_QUEUE', 'notifications-high'),
        'normal' => env('NOTIFICATIONS_NORMAL_QUEUE', 'notifications-normal'),
        'broadcasts' => env('NOTIFICATIONS_BROADCAST_QUEUE', 'broadcasts'),
    ],

    'tries' => (int) env('NOTIFICATIONS_QUEUE_TRIES', 3),

    'backoff' => [30, 120, 600],

    'health' => [
        'max_pending_per_queue' => (int) env('NOTIFICATIONS_QUEUE_MAX_PENDING', 100),
        'reverb_timeout_seconds' => (int) env('NOTIFICATIONS_REVERB_HEALTH_TIMEOUT', 2),
    ],
];
