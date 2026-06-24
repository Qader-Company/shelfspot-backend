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

## Company Endpoints

### Show task

```http
GET /api/v1/company/tasks/{task}
```

#### Required headers

```http
X-Company-Slug: {company_slug}
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
    "auto_accepted_at": null,
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
    "auto_accept_at": null,
    "auto_accepted_at": null
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
