# Real-time notifications

Notifications are persisted for the recipient `User` and broadcast through Laravel's private notification channel (`App.Models.User.{id}`). The client should listen with `Echo.private(...).notification(...)`, update its unread counter, then fetch the target resource through the normal authorised API.

## Required services

1. Run database migrations, including `create_notifications_table`.
2. Set `BROADCAST_CONNECTION=reverb` and configure the `REVERB_*` variables.
3. Run a queue worker and Reverb server:

```text
php artisan queue:work
php artisan reverb:start
```

## Notification API

- `GET /api/v1/{admin|company|worker}/notifications`
- `GET /api/v1/{admin|company|worker}/notifications/unread-count`
- `PATCH /api/v1/{admin|company|worker}/notifications/{id}/read`
- `PATCH /api/v1/{admin|company|worker}/notifications/read-all`

All payloads include `event`, `category`, `priority`, `action`, and context under `meta`. Current delivery rules are intentionally narrow: companies receive task completion, task failure, and reopening; admins receive worker cancellations and company rejections; workers receive nearby published tasks, reassignment, and reopening. Push delivery can reuse the same persisted payload later without changing task workflows.

## Notification Lab test sender

The sender is an opt-in development tool. Enable it with
`NOTIFICATION_LAB_SEND_ENABLED=true`; it is disabled by default. The sender
accepts at most one explicit user ID for each portal, verifies that every user
belongs to the selected portal, and marks every payload with `meta.is_test` and
a unique `meta.test_run_id`.

The feature is intentionally isolated so it can be removed without touching the
notification delivery infrastructure:

1. Delete `app/Support/NotificationLab`, `routes/notification-lab.php`,
   `config/notification_lab.php`, and `resources/views/notification-lab/sender.blade.php`.
2. Remove the conditional route include from `routes/web.php` and the conditional
   sender include from `resources/views/notification-lab.blade.php`.
3. Remove the sender block guarded by `#test-sender` from
   `resources/js/notification-lab.js` and delete the environment flag.
