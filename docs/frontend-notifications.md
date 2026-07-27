# Frontend notification integration

This is the contract for an external ShelfSpots frontend. Notifications have two complementary delivery paths:

1. **REST API** is the persisted source of truth. Use it for the initial screen, unread counts, missed events, and read state.
2. **Realtime (Laravel Reverb)** delivers a newly created notification while the user is online. It never replays notifications sent before a client subscribed.

Always load the REST API after login and after every realtime reconnect. A websocket notification should be inserted into the local list immediately, but it does not replace the next REST refresh.

## 1. Requirements and authentication

Use the portal that matches the authenticated token's Sanctum ability:

| Portal | Required Sanctum ability | Notification URL prefix |
| --- | --- | --- |
| Admin | `admin,access` | `/api/v1/admin/notifications` |
| Company | `company,access` | `/api/v1/company/notifications` |
| Worker | `worker,access` | `/api/v1/worker/notifications` |

### REST headers

Every notification REST request requires both headers:

```http
Accept: application/json
Authorization: Bearer <sanctum-access-token>
X-Authorization: <platform-api-key>
```

### Realtime authorization header

The private-channel authorization request (`POST /broadcasting/auth`) requires only the Bearer token:

```http
Accept: application/json
Authorization: Bearer <sanctum-access-token>
```

Do not expose `REVERB_APP_SECRET` or the platform API key in source control. The Reverb app key is public and may be provided to the browser; the secret is backend-only.

For a frontend hosted on a different origin, the backend deployment must include that exact origin in `FRONTEND_ORIGINS` and clear its config cache.

## 2. REST API

Replace `{portal}` below with `admin`, `company`, or `worker`.

| Purpose | Method | Path | Parameters / body |
| --- | --- | --- | --- |
| List persisted notifications | `GET` | `/api/v1/{portal}/notifications` | `per_page` optional, `1..100`, default `20`; `unread_only=1` optional |
| Get unread count | `GET` | `/api/v1/{portal}/notifications/unread-count` | None |
| Mark one notification read | `PATCH` | `/api/v1/{portal}/notifications/{notificationId}/read` | None |
| Mark every notification read | `PATCH` | `/api/v1/{portal}/notifications/read-all` | None |

The notification ID is a UUID. A user may only read or mark their own notifications; an ID belonging to another user returns `404`.

### Standard success envelope

All successful responses have this outer envelope:

```json
{
  "success": true,
  "data": {}
}
```

`PATCH` responses may additionally contain a localized `message`; do not use that message as application logic.

### List response

```http
GET /api/v1/admin/notifications?per_page=20&unread_only=1
```

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": "7ec2e819-c2fb-43bc-94d9-98dd5d18da1a",
        "type": "App\\Notifications\\RealtimeNotification",
        "data": {
          "event": "task.rejected",
          "category": "task",
          "priority": "high",
          "task_id": 42,
          "company_id": 7,
          "status": "rejected",
          "actor_id": 15,
          "action": { "resource": "task", "id": 42 },
          "meta": {
            "actor_type": "company",
            "reason": "Submitted work needs correction.",
            "from_status": "completed",
            "to_status": "rejected",
            "status_history_id": 145
          },
          "occurred_at": "2026-07-27T12:00:00+00:00"
        },
        "read_at": null,
        "created_at": "2026-07-27T12:00:02+00:00"
      }
    ],
    "links": {
      "first": "https://api.example.com/api/v1/admin/notifications?page=1",
      "last": "https://api.example.com/api/v1/admin/notifications?page=1",
      "prev": null,
      "next": null
    },
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 20,
      "to": 1,
      "total": 1
    }
  }
}
```

The persisted item shape is:

```ts
type PersistedNotification = {
  id: string;
  type: 'App\\Notifications\\RealtimeNotification' | string;
  data: TaskNotificationPayload;
  read_at: string | null;
  created_at: string | null;
};
```

### Unread count response

```json
{
  "success": true,
  "data": { "unread_count": 3 }
}
```

### Mark-one-read response

```json
{
  "success": true,
  "message": "Updated successfully",
  "data": {
    "id": "7ec2e819-c2fb-43bc-94d9-98dd5d18da1a",
    "type": "App\\Notifications\\RealtimeNotification",
    "data": { "event": "task.rejected" },
    "read_at": "2026-07-27T12:05:00+00:00",
    "created_at": "2026-07-27T12:00:02+00:00"
  }
}
```

### Mark-all-read response

```json
{
  "success": true,
  "message": "Updated successfully",
  "data": { "unread_count": 0 }
}
```

### Error handling

| Status | Meaning | Frontend behavior |
| --- | --- | --- |
| `401` | Missing, expired, or invalid Bearer token | Refresh/login again; disconnect realtime. |
| `403` | Token does not have the required portal access | Do not retry; use the correct portal/token. |
| `404` | Notification does not exist for the current user | Remove it from local state, then refresh the current page. |
| `422` | Invalid query/body | Show or log validation details. |
| `429` | Rate limited | Respect the `Retry-After` header before retrying. |

## 3. Persisted payload contract

`PersistedNotification.data` always uses the following shape for current task notifications:

```ts
type TaskNotificationPayload = {
  event: TaskNotificationEvent;
  category: 'task';
  priority: 'normal' | 'high';
  task_id: number;
  company_id: number;
  status: string;
  actor_id: number | null;
  action: {
    resource: 'task';
    id: number;
  };
  meta: {
    status_history_id: number;
    from_status: string;
    to_status: string;
    [key: string]: unknown;
  };
  occurred_at: string; // ISO-8601 timestamp
};

type TaskNotificationEvent =
  | 'task.published'
  | 'task.reassigned'
  | 'task.reopened'
  | 'task.completed'
  | 'task.failed'
  | 'task.worker_cancelled'
  | 'task.rejected';
```

### Contract rules

- `event`, `category`, `priority`, `task_id`, `company_id`, `status`, `actor_id`, `action`, `meta.status_history_id`, and `occurred_at` are the stable fields.
- `action` is always the navigation target. For current notifications, navigate to task details using `action.id`.
- `actor_id` can be `null` when a scheduler produced the status change.
- `occurred_at` is when the task event occurred; `created_at` is when its database notification was written. Do not assume the two timestamps are equal.
- `meta` is event-specific. Read only documented keys for the matching event and ignore unknown keys so future backend additions do not break the UI.
- The backend does **not** send a presentation-ready `title`, `body`, or translation key today. Map the `event` and known `meta` fields to the frontend's localized copy.

## 4. Realtime connection

### Environment values needed by the frontend

```dotenv
VITE_REVERB_APP_KEY=<public-reverb-app-key>
VITE_REVERB_HOST=<reverb-hostname>
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

For local development in this project, Reverb normally uses `localhost`, port `8080`, and `http`.

Install the client libraries:

```bash
npm install laravel-echo pusher-js
```

### One private channel per authenticated user

Subscribe after the token and authenticated numeric user ID are known:

```text
App.Models.User.{userId}
```

For user ID `2`, Echo subscribes to the underlying Reverb channel `private-App.Models.User.2`.

The backend authorizes only when the Bearer-token user's ID equals `{userId}`. Never accept an arbitrary ID from the URL, route params, or local storage as the subscription target.

### Echo setup

```ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const apiOrigin = 'https://api.example.com';

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT),
  forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
  enabledTransports: ['ws', 'wss'],
  authEndpoint: `${apiOrigin}/broadcasting/auth`,
  auth: {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${accessToken}`,
    },
  },
});

const userChannel = echo.private(`App.Models.User.${authenticatedUser.id}`);

userChannel
  .subscribed(() => {
    // The private channel is authorized. It is now safe to consider realtime online.
  })
  .notification((notification: RealtimeNotification) => {
    upsertRealtimeNotification(notification);
  })
  .error((error) => {
    // Usually 401/403 from POST /broadcasting/auth, a wrong user ID, or a token issue.
    reportRealtimeAuthorizationError(error);
  });
```

Do not call `/broadcasting/auth` yourself: Echo calls it while subscribing.

On logout, token replacement, portal switch, or app teardown:

```ts
echo.disconnect();
```

### Realtime payload body

The realtime callback receives the task payload fields **directly** (unlike the REST item, where they are under `item.data`) plus a notification ID and type:

```ts
type RealtimeNotification = TaskNotificationPayload & {
  id: string;
  type: string; // e.g. "shelfspot.notification.task.rejected"
};
```

Example received by `.notification(...)`:

```json
{
  "event": "task.rejected",
  "category": "task",
  "priority": "high",
  "task_id": 42,
  "company_id": 7,
  "status": "rejected",
  "actor_id": 15,
  "action": { "resource": "task", "id": 42 },
  "meta": {
    "actor_type": "company",
    "reason": "Submitted work needs correction.",
    "from_status": "completed",
    "to_status": "rejected",
    "status_history_id": 145
  },
  "occurred_at": "2026-07-27T12:00:00+00:00",
  "id": "7ec2e819-c2fb-43bc-94d9-98dd5d18da1a",
  "type": "shelfspot.notification.task.rejected"
}
```

Use `.notification(...)`; do not listen to `shelfspot.notification.*` as an Echo event name. Laravel broadcasts notification events under its notification event, while the custom `type` identifies the notification in the payload.

## 5. Events, recipients, and event-specific metadata

| Event | When it is emitted | Recipients | Priority | Event-specific `meta` keys |
| --- | --- | --- | --- | --- |
| `task.published` | A task moves `draft` -> `pending` | Available workers within 5 km, capped by the backend | `normal` | None beyond common keys |
| `task.reassigned` | An admin assigns/reassigns a task to a worker | Newly assigned worker | `high` | `reassigned_worker_id`, `assignment_type` |
| `task.reopened` | An admin reopens a task and assigns a worker | Newly assigned worker and eligible company users | `high` | `actor_type`, `reason` (nullable), `previous_worker_id`, `assigned_worker_id`, `assignment_type`, `reopen_deadline_at` |
| `task.completed` | A worker submits/completes the task | Eligible company users | `high` | None beyond common keys |
| `task.failed` | A pending task expires, or a reopened task expires | Eligible company users | `high` | Always a failure reason; reopened expiry also has `reopen_deadline_at`, `previous_worker_id` |
| `task.worker_cancelled` | A worker cancels a task | Active admins allowed to reassign tasks | `high` | `worker_id`, `reason` |
| `task.rejected` | A company rejects a completed task | Active admins allowed to reassign tasks | `high` | `actor_type: "company"`, `reason` |

Common metadata is always appended to every event:

```json
{
  "from_status": "...",
  "to_status": "...",
  "status_history_id": 145
}
```

### Recipient eligibility is backend-controlled

The frontend must not infer recipients or use the payload as an authorization grant:

- Company recipients are active company users with `view_task` through a role belonging to the same company.
- Admin recipients are active admins with `reassign_task`.
- Worker publish recipients are selected from workers available within the 5 km radius.
- The actor who performed an action is excluded when the actor is also a possible recipient.

## 6. Recommended client state flow

```text
Login / token available
        |
        +--> GET notifications + GET unread-count
        |
        +--> Subscribe to App.Models.User.{authenticatedUserId}
                  |
                  +--> subscription succeeds: realtime is online
                  |
                  +--> notification arrives: upsert by id, update unread badge, show toast if appropriate
                  |
                  +--> reconnect succeeds: refetch list and unread count
```

Recommended rules:

1. Store REST items as `{ id, data, read_at, created_at }`.
2. Convert a realtime payload to the same local shape only if needed by the UI; its task fields are not nested under `data`.
3. De-duplicate by notification `id`, not only by `task_id`. Multiple valid task events can exist for one task.
4. Set the unread badge from the server's unread-count after every initial load/reconnect. Incrementing locally is only an optimistic UI detail.
5. Mark a notification as read after the user opens its destination, or expose a separate explicit read action.
6. Treat websocket delivery as at-least-once and non-replayable. The REST refresh handles duplicates and messages missed while offline.

## 7. Troubleshooting checklist

| Symptom | Check |
| --- | --- |
| REST returns `401` | Bearer token is valid and sent; confirm the portal ability. |
| REST returns `403` | Use the correct portal/token and include `X-Authorization`. |
| Socket connects but no notifications arrive | Confirm the UI reports **Channel subscribed**, not merely **Socket connected**. Then verify subscription channel is exactly `App.Models.User.{authenticatedUser.id}`. |
| Channel authorization fails | Inspect `POST /broadcasting/auth`; it must return `200` and includes only the Bearer token. Ensure channel ID equals the token user ID. |
| A new REST notification exists but realtime did not show it | Verify the notification queue worker includes `notifications-high,notifications-normal,broadcasts`, Reverb is running, and the event occurred after the channel subscribed. |
| Events stop after deploy | Restart queue workers after backend code/config changes, and ensure the queue worker processes all notification queues. |

Backend local development commands:

```bash
php artisan reverb:start
php artisan queue:work database --queue=notifications-high,notifications-normal,broadcasts --tries=3 --backoff=30,120,600
```
