# Worker Mobile Push Notifications Integration

## 1. Scope

This document describes how the ShelfSpots worker mobile application integrates with Firebase Cloud Messaging (FCM), Reverb realtime notifications, and the persisted notification REST API.

Worker notifications use three delivery paths in parallel:

1. **Database/REST** is the source of truth for notification history and read state.
2. **Reverb** delivers notifications instantly while the application has an active realtime connection.
3. **Firebase Cloud Messaging** displays system push notifications and wakes the application while it is backgrounded or terminated.

FCM is enabled for worker users only. Admin and company users continue to use Database/REST and Reverb without Firebase push delivery.

## 2. Required request headers

All API requests in this document require:

```http
Accept: application/json
Content-Type: application/json
X-Authorization: <platform-api-key>
Authorization: Bearer <worker-access-token>
```

The Bearer token must be a worker access token, not a refresh, verification, or password-reset token.

## 3. Device-token lifecycle

### 3.1 Register or refresh a device

Call this endpoint after worker login, whenever FCM rotates the token, and on authenticated app startup to ensure the current token is registered.

```http
POST /api/v1/worker/account/device-tokens
```

Request:

```json
{
  "token": "<fcm-registration-token>",
  "device_type": "android",
  "device_name": "Samsung S24"
}
```

Fields:

| Field | Required | Rules |
| --- | --- | --- |
| `token` | Yes | String, maximum 512 characters |
| `device_type` | No | `android` or `ios` |
| `device_name` | No | String, maximum 255 characters |

Success response (`200`):

```json
{
  "success": true,
  "message": "Device registered for push notifications.",
  "data": {
    "id": 15,
    "device_type": "android",
    "device_name": "Samsung S24",
    "last_used_at": "2026-09-23T08:30:00.000000Z"
  }
}
```

A worker can register multiple devices. Registering an existing FCM token refreshes its device information and associates it with the currently authenticated worker.

### 3.2 FCM token refresh

Subscribe to the Firebase SDK token-refresh callback. Every newly issued token must immediately be sent to the registration endpoint while the worker is authenticated.

Do not assume an FCM token is permanent. It can change after an app reinstall, app-data reset, device restore, or Firebase rotation.

### 3.3 Remove a device token

```http
DELETE /api/v1/worker/account/device-tokens
```

Request:

```json
{
  "token": "<fcm-registration-token>"
}
```

Success response (`200`):

```json
{
  "success": true,
  "message": "Device removed from push notifications."
}
```

The endpoint is idempotent. Removing a token that is already absent still returns success.

### 3.4 Logout

The preferred logout request removes the device token and revokes the current access token in one call:

```http
DELETE /api/v1/auth/logout
```

```json
{
  "device_token": "<fcm-registration-token>"
}
```

If the application calls the device-token delete endpoint separately, it may send an empty logout body afterward. Remove the token before deleting local authentication state so the request can still use the worker access token.

## 4. Worker events delivered through Firebase

The following current events are sent through Database/REST, Reverb, and FCM:

| Event | When it happens | Priority | Expected destination |
| --- | --- | --- | --- |
| `task.published` | A new nearby task becomes available | Normal | Task details |
| `task.reassigned` | A task is assigned/reassigned to the worker | High | Task details |
| `task.reopened` | A task is reopened and assigned to the worker | High | Task details |

Any future `RealtimeNotification` addressed to a worker will automatically use the Firebase channel as well.

Company and admin events are not delivered through Firebase. For example, `task.completed`, `task.failed`, `task.worker_cancelled`, and `task.rejected` currently target company/admin users and therefore remain REST + Reverb only.

## 5. FCM message contract

The backend sends a combined FCM notification and data message.

### Notification section

```json
{
  "title": "Task assigned to you",
  "body": "Task #42 has been assigned to you."
}
```

Display the title and body supplied by FCM for system notifications.

### Data section

All values in `data` are strings, including IDs and JSON content:

```json
{
  "notification_id": "7ec2e819-c2fb-43bc-94d9-98dd5d18da1a",
  "event": "task.reassigned",
  "category": "task",
  "priority": "high",
  "task_id": "42",
  "company_id": "7",
  "status": "reassigned",
  "actor_id": "",
  "action_resource": "task",
  "action_id": "42",
  "meta": "{\"status_history_id\":145,\"assignment_type\":\"manual\"}",
  "occurred_at": "2026-09-23T08:30:00+00:00"
}
```

Client parsing rules:

- Parse `task_id`, `company_id`, `action_id`, and non-empty `actor_id` as integers when needed.
- JSON-decode `meta` before reading event-specific values.
- Ignore unknown fields so backend additions remain backward compatible.
- Use `action_resource` and `action_id` for navigation. Current worker notifications use `task` as the resource.
- Never treat an FCM payload as an authorization grant. The destination API still enforces worker authentication and access rules.

Task destination:

```http
GET /api/v1/worker/tasks/{action_id}
```

If the task is no longer available to the worker, handle `403` or `404` normally and refresh the notification list.

## 6. Preventing duplicate foreground notifications

An online worker can receive the same logical notification from Reverb and FCM. Use `notification_id` as the deduplication key.

Recommended behavior:

1. Keep a short-lived set of handled notification IDs.
2. When Reverb or FCM delivers an ID already in the set, do not add a second list item or show another in-app banner.
3. When the application is in the foreground, prefer the Reverb event for immediate UI updates.
4. Do not manually show an FCM foreground banner if Reverb has already shown the same notification.
5. On app resume, notification tap, or Reverb reconnect, reload the REST list and unread count.

REST remains the source of truth even if FCM or Reverb delivery is missed.

## 7. Notification REST endpoints

| Purpose | Method | Endpoint |
| --- | --- | --- |
| List notifications | `GET` | `/api/v1/worker/notifications` |
| Get unread count | `GET` | `/api/v1/worker/notifications/unread-count` |
| Mark one as read | `PATCH` | `/api/v1/worker/notifications/{notificationId}/read` |
| Mark all as read | `PATCH` | `/api/v1/worker/notifications/read-all` |

When the user opens a push notification:

1. Navigate to the task using `action_id`.
2. Mark `notification_id` as read when appropriate.
3. Refresh the unread count from the backend.

## 8. Android requirements

1. Add the Firebase `google-services.json` file for the worker application's package name.
2. Initialize Firebase before requesting an FCM token.
3. Request `POST_NOTIFICATIONS` permission on Android 13 and newer.
4. Create an Android notification channel with this exact ID:

```text
shelfspot_notifications
```

The channel ID must match the backend `FIREBASE_ANDROID_CHANNEL_ID` value. Create it before the first push arrives. The backend sends the default notification sound and uses high FCM priority for high-priority events.

5. Implement foreground, background, terminated-app, and notification-tap handlers.
6. Verify that release builds use the Firebase project intended for the production backend.

## 9. iOS requirements

1. Add the correct `GoogleService-Info.plist` for the worker bundle ID.
2. Enable Push Notifications and Background Modes/Remote notifications capabilities.
3. Upload or connect the production APNs authentication key in Firebase Console.
4. Request notification permission from the user.
5. Register for remote notifications and obtain the FCM token.
6. Configure the desired foreground presentation behavior.
7. Implement notification-tap handling for background and terminated states.

The backend sends `apns-push-type: alert`, immediate APNs priority, and the default sound.

## 10. Error handling

| Status | Meaning | Client action |
| --- | --- | --- |
| `401` | Access token missing, invalid, or expired | Refresh the session or log in again |
| `403` | Token is not allowed to access worker APIs | Do not retry with the same token |
| `422` | Invalid token/device request data | Fix the request and log validation details |
| `429` | Too many registration/deletion requests | Respect `Retry-After` before retrying |
| `500` | Unexpected backend error | Retry with bounded exponential backoff and report diagnostics |

Do not log the full FCM registration token in analytics, crash reports, or normal application logs.

## 11. Recommended application flow

```text
Worker logs in
    -> request notification permission when appropriate
    -> Firebase getToken()
    -> POST /worker/account/device-tokens
    -> load REST notifications and unread count
    -> connect Reverb

Firebase token rotates
    -> POST /worker/account/device-tokens with the new token

FCM or Reverb notification arrives
    -> deduplicate by notification_id
    -> update UI/show notification as appropriate
    -> on tap, open /worker/tasks/{action_id}
    -> refresh REST notification state

Worker logs out
    -> DELETE /auth/logout with device_token
    -> disconnect Reverb
    -> clear tokens and local notification state
```

## 12. QA checklist

- [ ] Fresh install permission accepted.
- [ ] Fresh install permission denied without crashing or blocking login.
- [ ] Device token registered after login.
- [ ] Token refresh updates backend registration.
- [ ] `task.published` push opens the correct task.
- [ ] `task.reassigned` push opens the correct task.
- [ ] `task.reopened` push opens the correct task.
- [ ] Push received with app in foreground.
- [ ] Push received with app in background.
- [ ] Push received with app terminated.
- [ ] Reverb + FCM do not create duplicate UI entries.
- [ ] Notification list and unread count recover after reconnect.
- [ ] Logout removes the device token.
- [ ] Logging into a different worker account re-associates the current token correctly.
- [ ] Android release build uses the correct channel and Firebase project.
- [ ] iOS production build receives APNs-delivered FCM notifications.

