<?php

return [
    /*
     * The sender is deliberately opt-in. Removing this file and the isolated
     * NotificationLab directory removes the backend feature completely.
     */
    'sending_enabled' => (bool) env('NOTIFICATION_LAB_SEND_ENABLED', false),
];
