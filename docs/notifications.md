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
