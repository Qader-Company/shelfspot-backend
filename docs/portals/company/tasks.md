# Flow: Company Task Management

## Description
Company Task Management documents how company users list, create, view, update, pay, request refund, and delete company tasks.

## Business Goal
Allow a company to create field-work tasks that include one or more services and products, reserve/pay the required wallet amount, monitor task progress, and manage draft or active task records.

## Module Overview
This flow belongs to the company portal Tasks module. Endpoints are under `/api/v1/company/tasks`, require a company Bearer token, resolve tenant data through `X-Company-Slug`, and enforce task permissions per action.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- The acting company user has the required permission: `view_task`, `create_task`, `edit_task`, or `delete_task`.
- Task date must be today or tomorrow in `Y-m-d` format.
- Each service must use an active service `service_key`, meet the service minimum price and execution time, and include at least one current-company product.
- Request files, when sent, must be `jpg`, `jpeg`, `png`, `webp`, or `pdf`, max `10240 KB`.

## Walkthrough
1. Call `List Tasks` to display company task history with optional filters.
2. Call `Create Task` with date, location, notes, services, products, and service request details.
3. Call `Pay Task` when a draft task should be charged from the company wallet.
4. Call `Show Task` to display task details, assigned worker, progress, services, and status timestamps.
5. Call `Update Task` only while the task is still editable.
6. Call `Request Refund` when eligible task payment should be refunded.
7. Call `Delete Task` when the task can be deleted.

## Endpoint: List Tasks
- **Method:** GET
- **URL:** /api/v1/company/tasks
- **Auth:** Bearer
- **Purpose:** Return paginated company tasks with optional filters.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 501,
        "company_id": 9,
        "date": "2026-07-04",
        "estimated_duration_minutes": 120,
        "location": {
          "latitude": "25.2854",
          "longitude": "51.5310",
          "location_name": "Main Branch",
          "address": "Doha"
        },
        "total_price": 300,
        "notes": "Visit before noon.",
        "status": "draft",
        "payment_status": "pending",
        "progress": {
          "total_services": 2,
          "completed_services": 0,
          "remaining_services": 2,
          "percentage": 0
        },
        "created_at": "2026-07-04 10:00:00",
        "updated_at": "2026-07-04 10:00:00"
      }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

### Examples
#### Example: List pending paid tasks
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "data": [
      { "id": 501, "status": "pending", "payment_status": "charged", "total_price": 300 }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
The endpoint accepts query filters: `status`, `payment_status`, `date_from`, and `date_to`.

## Endpoint: Create Task
- **Method:** POST
- **URL:** /api/v1/company/tasks
- **Auth:** Bearer
- **Purpose:** Create a company task draft with selected services and products.

### Headers
```
Accept: application/json
Content-Type: multipart/form-data
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{
  "date": "string (required, format:Y-m-d, today or tomorrow)",
  "location": {
    "latitude": "number (required, between:-90,90)",
    "longitude": "number (required, between:-180,180)",
    "location_name": "string (optional nullable, max:255)",
    "address": "string (optional nullable, max:2000)"
  },
  "notes": "string (optional nullable, max:5000)",
  "services": [
    {
      "service_key": "string (required, distinct, supported service key)",
      "price": "number (required, must be >= service minimum_price)",
      "execution_time_minutes": "integer (required, must be >= service minimum_execution_time)",
      "execution_instructions": "string (optional nullable, max:5000)",
      "products": [
        {
          "product_id": "integer (required, must belong to current company)",
          "product_details": "object (optional, service-specific product details)"
        }
      ],
      "request_files": "object (optional, service-specific uploaded files)"
    }
  ]
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Created successfully.",
  "data": {
    "id": 501,
    "date": "2026-07-04",
    "total_price": 300,
    "status": "draft",
    "payment_status": "pending"
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

- **422 — validation error**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "date": ["The date must be a date after or equal to today."],
    "services.0.price": ["The selected service price is below the minimum price."],
    "services.0.products.0.product_id": ["The selected product does not belong to the current company."]
  }
}
```

### Examples
#### Example: Create draft task
Request:
```json
{
  "date": "2026-07-04",
  "location": {
    "latitude": 25.2854,
    "longitude": 51.5310,
    "location_name": "Main Branch",
    "address": "Doha"
  },
  "notes": "Visit before noon.",
  "services": [
    {
      "service_key": "primary_display",
      "price": 150,
      "execution_time_minutes": 60,
      "execution_instructions": "Check main aisle display.",
      "products": [
        { "product_id": 10, "product_details": { "facing_count": 4 } }
      ]
    }
  ]
}
```
Response:
```json
{
  "success": true,
  "message": "Created successfully.",
  "data": {
    "id": 501,
    "status": "draft",
    "payment_status": "pending",
    "total_price": 150
  }
}
```

### Notes
`execution_time` is prohibited at the top level. Total task price is derived from service prices. Service-specific dynamic validation may require additional fields based on each `service_key`.

## Endpoint: Show Task
- **Method:** GET
- **URL:** /api/v1/company/tasks/{id}
- **Auth:** Bearer
- **Purpose:** Return one company task with detail relations.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "data": {
    "id": 501,
    "date": "2026-07-04",
    "total_price": 150,
    "status": "pending",
    "payment_status": "charged",
    "progress": {
      "total_services": 1,
      "completed_services": 0,
      "remaining_services": 1,
      "percentage": 0
    },
    "services": []
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

- **404 — task not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Show task detail
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "id": 501,
    "status": "pending",
    "payment_status": "charged",
    "progress": { "total_services": 1, "completed_services": 0, "remaining_services": 1, "percentage": 0 }
  }
}
```

### Notes
Company responses map `worker_cancelled` to `in_progress` for company-facing status display.

## Endpoint: Update Task
- **Method:** PATCH
- **URL:** /api/v1/company/tasks/{id}
- **Auth:** Bearer
- **Purpose:** Update an editable company task using the same request contract as create.

### Headers
```
Accept: application/json
Content-Type: multipart/form-data
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{
  "date": "string (required by current request rules, format:Y-m-d, today or tomorrow)",
  "location": "object (required by current request rules)",
  "services": "array (required by current request rules, min:1)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 501,
    "status": "draft",
    "payment_status": "pending"
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission or action not allowed**
```json
{ "success": false, "message": "Forbidden." }
```

- **422 — validation error**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "services": ["The services field is required."]
  }
}
```

### Examples
#### Example: Update draft task services
Request:
```json
{
  "date": "2026-07-04",
  "location": { "latitude": 25.2854, "longitude": 51.5310 },
  "services": [
    {
      "service_key": "primary_display",
      "price": 180,
      "execution_time_minutes": 60,
      "products": [{ "product_id": 10 }]
    }
  ]
}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": { "id": 501, "status": "draft", "payment_status": "pending" }
}
```

### Notes
The update endpoint currently uses the same request class as create, so clients should send the full task payload rather than a sparse partial payload.

## Endpoint: Pay Task
- **Method:** POST
- **URL:** /api/v1/company/tasks/{id}/pay
- **Auth:** Bearer
- **Purpose:** Charge the company wallet for a draft task and move it into the paid workflow.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 501,
    "status": "pending",
    "payment_status": "charged",
    "charged_at": "2026-07-04 10:15:00"
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission or action not allowed**
```json
{ "success": false, "message": "Forbidden." }
```

- **422 — insufficient wallet balance**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "wallet": ["The company wallet balance is not enough for this task."]
  }
}
```

### Examples
#### Example: Pay draft task
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": { "id": 501, "status": "pending", "payment_status": "charged" }
}
```

### Notes
This endpoint requires `create_task` permission because paying a draft transitions it into the created/paid task workflow.

## Endpoint: Request Refund
- **Method:** POST
- **URL:** /api/v1/company/tasks/{id}/request-refund
- **Auth:** Bearer
- **Purpose:** Request a wallet refund for an eligible company task.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 501,
    "status": "refund_requested",
    "payment_status": "charged"
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission or action not allowed**
```json
{ "success": false, "message": "Forbidden." }
```

### Examples
#### Example: Request refund
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": { "id": 501, "status": "refund_requested", "payment_status": "charged" }
}
```

### Notes
This endpoint requires `edit_task` permission and is controlled by task action rules.

## Endpoint: Delete Task
- **Method:** DELETE
- **URL:** /api/v1/company/tasks/{id}
- **Auth:** Bearer
- **Purpose:** Delete a company task when task action rules allow deletion.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "message": "Deleted successfully."
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission or action not allowed**
```json
{ "success": false, "message": "Forbidden." }
```

- **404 — task not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Delete draft task
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Deleted successfully."
}
```

### Notes
Deletion is guarded by task action rules and tenant ownership.

## Branch: Draft task payment
**Condition:** A company creates a task draft and wants workers to see it.

### Case: Charge wallet and publish task
**When:** The company wallet has enough balance and the task is eligible to be paid.
**Explanation:** Call `Pay Task`; if it succeeds, update the UI from `draft/pending` payment state to the returned task state and payment status.

#### Endpoint: Pay Task
- **Method:** POST
- **URL:** /api/v1/company/tasks/{id}/pay

---
# Flow: Company Task Review

## Description
Company Task Review documents how a company accepts or rejects completed task delivery and exchanges review messages with admins when a task is rejected.

## Business Goal
Allow companies to approve completed work, reject work with a reason, and coordinate review messages for rejected or reopened tasks.

## Module Overview
This flow belongs to the company portal task lifecycle. Accept and reject endpoints operate on completed/reviewable tasks. Review messages are available for `rejected`, `reopened`, and `accepted` tasks, while writing messages is limited to rejected tasks.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_task` permission.
- Reject reason must be a string between 10 and 2000 characters.
- Review message must be a string with max 5000 characters.

## Walkthrough
1. Company opens a completed task.
2. Company calls `Accept Task` if delivery is correct.
3. Company calls `Reject Task` with a required reason if delivery is not correct.
4. If rejected, company can call `List Review Messages` and `Create Review Message`.
5. If admin reopens the task, company can still view review messages.

## Endpoint: Accept Task
- **Method:** PATCH
- **URL:** /api/v1/company/tasks/{id}/accept
- **Auth:** Bearer
- **Purpose:** Accept completed task delivery.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 501,
    "status": "accepted",
    "company_accepted_at": "2026-07-04 16:00:00"
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission or action not allowed**
```json
{ "success": false, "message": "Forbidden." }
```

### Examples
#### Example: Accept completed task
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": { "id": 501, "status": "accepted" }
}
```

### Notes
This endpoint requires `view_task` permission.

## Endpoint: Reject Task
- **Method:** POST
- **URL:** /api/v1/company/tasks/{id}/reject
- **Auth:** Bearer
- **Purpose:** Reject completed task delivery with a required reason.

### Headers
```
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{
  "reason": "string (required, min:10, max:2000)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 501,
    "status": "rejected",
    "rejected_at": "2026-07-04 16:00:00",
    "rejection_reason": "Photos do not show the requested display clearly."
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission or action not allowed**
```json
{ "success": false, "message": "Forbidden." }
```

- **422 — validation error**
```json
{
  "message": "The reason field is required.",
  "errors": {
    "reason": ["The reason field is required."]
  }
}
```

### Examples
#### Example: Reject completed task
Request:
```json
{
  "reason": "Photos do not show the requested display clearly."
}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": { "id": 501, "status": "rejected", "rejection_reason": "Photos do not show the requested display clearly." }
}
```

### Notes
Reject creates a rejected task state that allows review messages.

## Endpoint: List Review Messages
- **Method:** GET
- **URL:** /api/v1/company/tasks/{id}/review-messages
- **Auth:** Bearer
- **Purpose:** List task review messages for rejected, reopened, or accepted tasks.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "task_id": 501,
        "sender_role": "company",
        "sender": {
          "id": 15,
          "name": "Company Owner",
          "email": "owner@example.com"
        },
        "message": "Please review the rejected delivery notes.",
        "created_at": "2026-07-04 16:10:00"
      }
    ]
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **422 — messages unavailable**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "task": ["Review messages are unavailable for this task status."]
  }
}
```

### Examples
#### Example: List rejected task messages
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "data": [
      { "id": 1, "task_id": 501, "sender_role": "company", "message": "Please review the rejected delivery notes." }
    ]
  }
}
```

### Notes
Messages are returned oldest-first for company endpoints.

## Endpoint: Create Review Message
- **Method:** POST
- **URL:** /api/v1/company/tasks/{id}/review-messages
- **Auth:** Bearer
- **Purpose:** Add a company review message to a rejected task.

### Headers
```
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{
  "message": "string (required, max:5000)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Created successfully.",
  "data": {
    "id": 2,
    "task_id": 501,
    "sender_role": "company",
    "message": "Please reopen this task after worker correction.",
    "created_at": "2026-07-04 16:15:00"
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **422 — validation error**
```json
{
  "message": "The message field is required.",
  "errors": {
    "message": ["The message field is required."]
  }
}
```

- **422 — not rejected**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "task": ["Review messages can be added only while the task is rejected."]
  }
}
```

### Examples
#### Example: Add review message
Request:
```json
{
  "message": "Please reopen this task after worker correction."
}
```
Response:
```json
{
  "success": true,
  "message": "Created successfully.",
  "data": { "id": 2, "task_id": 501, "sender_role": "company", "message": "Please reopen this task after worker correction." }
}
```

### Notes
Writing messages is allowed only while the task status is `rejected`.

## Branch: Reject and discuss task delivery
**Condition:** The company rejects a completed task and wants admin follow-up.

### Case: Rejection review conversation
**When:** The task is in `rejected` status.
**Explanation:** Use `Reject Task` with a reason, then use review messages to communicate details. The admin can later reopen the task from the admin portal.

#### Endpoint: Create Review Message
- **Method:** POST
- **URL:** /api/v1/company/tasks/{id}/review-messages

---
# Flow: Company Task Trash Management

## Description
Company Task Trash Management documents how company users list deleted tasks, restore a deleted task, and permanently purge a deleted task.

## Business Goal
Allow companies to recover deleted tasks when possible, or permanently remove deleted task records when business rules allow it.

## Module Overview
This flow belongs to company task trash handling. Trash endpoints are under `/api/v1/company/tasks/trash` and operate only on deleted tasks owned by the current company.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_task` for trash list, `edit_task` for restore, and `delete_task` for purge.

## Walkthrough
1. Call `List Trashed Tasks` to display deleted tasks.
2. Call `Restore Task` when a deleted task should return to the normal task list.
3. Call `Purge Task` when a deleted task should be permanently removed.

## Endpoint: List Trashed Tasks
- **Method:** GET
- **URL:** /api/v1/company/tasks/trash
- **Auth:** Bearer
- **Purpose:** Return paginated deleted tasks owned by the current company.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "data": {
    "data": [
      { "id": 501, "status": "draft", "payment_status": "pending", "total_price": 150 }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

### Examples
#### Example: List deleted draft tasks
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "data": [{ "id": 501, "status": "draft", "payment_status": "pending" }],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
The endpoint accepts the same filters as the normal list: `status`, `payment_status`, `date_from`, and `date_to`.

## Endpoint: Restore Task
- **Method:** POST
- **URL:** /api/v1/company/tasks/trash/{id}/restore
- **Auth:** Bearer
- **Purpose:** Restore one deleted task owned by the current company.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": { "id": 501, "status": "draft", "payment_status": "pending" }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission or action not allowed**
```json
{ "success": false, "message": "Forbidden." }
```

- **404 — deleted task not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Restore deleted task
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": { "id": 501, "status": "draft" }
}
```

### Notes
Restore requires `edit_task` permission.

## Endpoint: Purge Task
- **Method:** DELETE
- **URL:** /api/v1/company/tasks/trash/{id}
- **Auth:** Bearer
- **Purpose:** Permanently purge one deleted task owned by the current company.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "message": "Deleted successfully."
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission or action not allowed**
```json
{ "success": false, "message": "Forbidden." }
```

- **404 — deleted task not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Purge deleted task
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Deleted successfully."
}
```

### Notes
Purge requires `delete_task` permission and should be treated as irreversible.

## Branch: Deleted task should be recovered
**Condition:** A company user deleted a task by mistake.

### Case: Restore deleted task
**When:** The task appears in `List Trashed Tasks` and action rules allow restore.
**Explanation:** Call `Restore Task`, then reload `List Tasks` to confirm it returned to the normal task list.

#### Endpoint: Restore Task
- **Method:** POST
- **URL:** /api/v1/company/tasks/trash/{id}/restore
