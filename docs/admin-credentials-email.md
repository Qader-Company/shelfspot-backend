# Admin credentials emails

The following account-management endpoints email credentials to the account's saved email address:

| Action | Company portal | Admin portal |
| --- | --- | --- |
| Create account | `POST /api/v1/company/access-control/admins` | `POST /api/v1/admin/access-control/admins` |
| Update password | `PUT` or `PATCH /api/v1/company/access-control/admins/{user}` | `PUT` or `PATCH /api/v1/admin/access-control/admins/{user}` |

Creation emails include the account name, email address, and submitted password. Updates send an email only when the validated request includes `password`. If the same update changes the email address, the credentials go to the updated address. Profile, status, email, or role edits without a password do not send credentials.

Emails are dispatched after the account transaction commits. Validation failures, rejected account changes, and rolled-back transactions do not send emails. Passwords remain hashed in the users table and are excluded from API responses.

`SendAdminCredentialsEmailJob` encrypts its queued payload, retries delivery up to three times, and uses `notifications.queues.normal` (`notifications-normal` by default, configurable with `NOTIFICATIONS_NORMAL_QUEUE`). Delivery uses the existing Laravel mail configuration. For asynchronous queue connections, run a worker listening to that queue, for example `php artisan queue:work --queue=notifications-normal`. With the `sync` connection, delivery runs during the request after the transaction commits.
