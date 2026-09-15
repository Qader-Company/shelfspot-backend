# Task Lifecycle API Contract

## الهدف

تثبيت شكل الـ API response والـ endpoints الخاصة بمرحلة مراجعة تسليم التاسك، عشان الـ frontend يقدر يبني UI ثابت للشركة والأدمن بدون اختلاف في أسماء الحقول أو الحالات.

## حالات المراجعة المهمة للـ Frontend

| Status | يظهر لمين | المعنى | الأكشن المتاح |
| --- | --- | --- | --- |
| `completed` | company/admin | العامل خلص التسليم والتاسك في فترة مراجعة الشركة. | Company accept/reject. |
| `rejected` | company/admin | الشركة رفضت التسليم وكتبت سبب. | Company can accept, admin can message/reopen. |
| `accepted` | company/admin/worker | التسليم اتقبل يدويًا أو تلقائيًا. | Read-only. |
| `reopened` | company/admin/worker | الأدمن أعاد فتح التاسك بعد الرفض. | Worker can execute again. |
| `reassigned` | admin/worker | الأدمن أسند التاسك لعامل جديد وينتظر العامل أن يقبلها. | Assigned worker can start or cancel. |

## Reassignment Flow

```text
worker_cancelled/started/reassigned
    -> reassigned       (admin assigns a worker)
    -> started          (the assigned worker calls /start)
    -> in_progress      (the assigned worker checks in through /execute)
```

- `PATCH /api/v1/admin/tasks/{task}/reassign` assigns the worker and creates a `reassigned` assignment without starting the check-in timer.
- Only the assigned worker can call `POST /api/v1/worker/tasks/{task}/start` while the task is `reassigned`.
- The worker's start action keeps the existing reassignment record and starts the 15-minute check-in deadline.
- Company APIs expose the recovery flow as `in_progress`; admin and worker APIs expose the internal status.

## Worker Start Timer

`POST /api/v1/worker/tasks/{task}/start` returns `start_deadline_remaining_minutes` alongside `start_deadline_at`. The same field appears in the worker's task list and task details. It is the remaining time to reach the store and call `/execute`, not the time to finish the service.

```json
{
  "status": "started",
  "start_deadline_at": "2026-09-15 12:15:00",
  "start_deadline_remaining_minutes": 15
}
```

`POST /api/v1/worker/tasks/{task}/extend-start-deadline` recalculates the number from the new deadline. For example, a 10-minute extension after 5 minutes have passed returns `20`. The value is `0` once the deadline has passed and `null` when the task is no longer `started`.

## Company Endpoints

### Show task

```http
GET /api/v1/company/tasks/{task}
```

#### Required headers

```http
X-Company-id: {company_id}
Authorization: Bearer {company_access_token}
```

#### Response fields required by UI

```json
{
  "data": {
    "id": 1,
    "status": "completed",
    "completed_at": "2026-06-10 09:00:00",
    "rejected_at": null,
    "rejection_reason": null,
    "company_accepted_at": null,
    "auto_accept_at": "2026-06-12 01:00:00",
    "reopened_at": null,
    "reopen_reason": null,
    "progress": {
      "total_services": 1,
      "completed_services": 1,
      "remaining_services": 0,
      "percentage": 100
    },
    "services": []
  }
}
```

### Accept completed or rejected task

```http
POST /api/v1/company/tasks/{task}/accept
```

#### Allowed transitions

```text
completed -> accepted
rejected -> accepted
```

#### Successful response expectation

```json
{
  "data": {
    "status": "accepted",
    "company_accepted_at": "2026-06-10 09:00:00"
  }
}
```

### Reject completed task

```http
POST /api/v1/company/tasks/{task}/reject
```

#### Payload

```json
{
  "reason": "Submitted photos are not clear enough."
}
```

#### Rules

- `reason` is required.
- Task must be `completed`.
- `now()` must be before `auto_accept_at`.

#### Successful response expectation

```json
{
  "data": {
    "status": "rejected",
    "rejected_at": "2026-06-10 09:00:00",
    "rejection_reason": "Submitted photos are not clear enough."
  }
}
```

#### Failure after review window

```json
{
  "success": false,
  "errors": {
    "task": ["The review window has expired; this task can no longer be rejected."]
  }
}
```

## Admin Endpoints

### Reopen rejected task

```http
POST /api/v1/admin/tasks/{task}/reopen
```

#### Payload

```json
{
  "reason": "Company rejection is valid."
}
```

#### Allowed transition

```text
rejected -> reopened
```

#### Successful response expectation

```json
{
  "data": {
    "status": "reopened",
    "reopened_at": "2026-06-10 09:00:00",
    "reopen_reason": "Company rejection is valid.",
    "auto_accept_at": null
  }
}
```

## Review Messages Endpoints

### Company messages

```http
GET  /api/v1/company/tasks/{task}/review-messages
POST /api/v1/company/tasks/{task}/review-messages
```

### Admin messages

```http
GET  /api/v1/admin/tasks/{task}/review-messages
POST /api/v1/admin/tasks/{task}/review-messages
```

### Message payload

```json
{
  "message": "Please review the rejection details."
}
```

### Message response

```json
{
  "data": {
    "id": 1,
    "task_id": 10,
    "sender_role": "company",
    "message": "Please review the rejection details.",
    "created_at": "2026-06-10 09:00:00"
  }
}
```

## Manual E2E Scenario Checklist

1. Worker completes a task and status becomes `completed`.
2. Company opens task details and sees `auto_accept_at`, `progress`, and service submissions.
3. Company rejects with `reason` before `auto_accept_at`.
4. Company and admin exchange review messages.
5. Admin reopens the rejected task.
6. Worker executes the reopened task again.
7. Worker updates the same service submissions.
8. Worker completes again and a new `auto_accept_at` is calculated.
9. Company accepts, or the command auto-accepts after the deadline.
