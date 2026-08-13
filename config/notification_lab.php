<?php

return [
    /*
     * The sender is deliberately opt-in. Removing this file and the isolated
     * NotificationLab directory removes the backend feature completely.
     */
    'sending_enabled' => (bool) env('NOTIFICATION_LAB_SEND_ENABLED', false),

    /*
     * Test events are server-owned fixtures. The lab only accepts one of these
     * event names and builds the rest of the payload from this definition.
     */
    'events' => [
        'task.published' => ['label' => 'Task published', 'priority' => 'normal', 'status' => 'pending'],
        'task.completed' => ['label' => 'Task completed', 'priority' => 'high', 'status' => 'completed'],
        'task.failed' => ['label' => 'Task failed', 'priority' => 'high', 'status' => 'failed'],
        'task.rejected' => ['label' => 'Task rejected', 'priority' => 'high', 'status' => 'rejected'],
        'task.reopened' => ['label' => 'Task reopened', 'priority' => 'high', 'status' => 'reopened'],
        'task.reassigned' => ['label' => 'Task reassigned', 'priority' => 'high', 'status' => 'started'],
        'task.worker_cancelled' => ['label' => 'Worker cancelled', 'priority' => 'high', 'status' => 'worker_cancelled'],
    ],

    'fixtures' => [
        'task_id' => 1001,
        'company_id' => 2001,
        'status_history_id' => 3001,
    ],
];
