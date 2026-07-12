<?php

return [
    'task_status' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'started' => 'Started',
        'in_progress' => 'In progress',
        'worker_cancelled' => 'Worker cancelled',
        'company_cancelled' => 'Cancelled by company',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'accepted' => 'Accepted',
        'reopened' => 'Reopened',
        'failed' => 'Failed',
    ],
    'task_payment_status' => [
        'pending' => 'Pending',
        'charged' => 'Charged',
        'refunded' => 'Refunded',
        'failed' => 'Failed',
    ],
    'task_service_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
    ],
    'catalog_purge_status' => [
        'queued' => 'Queued',
        'failed' => 'Failed',
    ],
];
