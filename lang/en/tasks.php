<?php

return [
    'validation' => [
        'accept_pending_only' => 'Only pending tasks can be accepted.',
        'accept_charged_only' => 'Task payment must be charged before acceptance.',
        'accept_deleted_task' => 'Deleted tasks cannot be accepted.',
        'accept_unassigned_only' => 'This task is already assigned to another worker.',
        'accept_execution_date_only' => 'Tasks can only be accepted on their execution date.',
        'start_accepted_only' => 'Only accepted tasks can be started.',
        'worker_not_assigned' => 'This task is not assigned to the current worker.',
        'start_deadline_expired' => 'The start window for this task has expired.',
        'start_outside_geofence' => 'You must be at or near the task location to start it.',
        'minimum_price' => 'The service price must be at least :price.',
        'minimum_execution_time' => 'The service execution time must be at least :minutes minutes.',
        'required_file' => 'This service requires the requested file upload.',
        'product_not_in_company' => 'The selected product does not belong to the current company.',
        'service_not_in_task' => 'This service does not belong to the selected task.',
        'submit_in_progress_only' => 'Task services can only be submitted after the task starts.',
        'submitted_product_not_in_task_service' => 'The submitted product is not part of this task service.',
    ],
];
