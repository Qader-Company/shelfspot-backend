# Flow: Company Task Reports

## Description
Company Task Reports documents how the company portal reads worker delivery reports from task details after workers submit service results. There is no standalone `/api/v1/company/reports` route in the current API; report data is exposed through task resources under `/api/v1/company/tasks`.

## Business Goal
Allow companies to review execution evidence, submitted form data, attachments, service completion status, and task progress from the same task detail contract used by the task lifecycle.

## Module Overview
This reporting flow belongs to the company Tasks module. Reports are represented as task service submissions inside `services[].submission` on task detail responses. Each service type has its own `submission_form` schema exposed by the Services API, and worker submitted values are returned in `submission.form_data` with submission attachments.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- The acting company user has `view_task` permission.
- Task belongs to the current company.
- A worker must have submitted one or more task services before report submission data appears.

## Walkthrough
1. Call `List Reportable Tasks` to find tasks by status and date range.
2. Select a task that has progress, completed services, or status `completed`, `rejected`, `accepted`, or `reopened`.
3. Call `Show Task Report` to load detailed task relations.
4. Read each service from `services[]` and inspect `service`, `products`, `attachments`, and `submission`.
5. Use `submission.form_data` and `submission.attachments` to render the report according to the service `submission_form` schema.
6. If report output is acceptable, use the existing task review flow to accept the task; if not, reject it with a reason.

## Endpoint: List Reportable Tasks
- **Method:** GET
- **URL:** /api/v1/company/tasks
- **Auth:** Bearer
- **Purpose:** Return company tasks that can be used as the entry point for report review.

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
        "date": "2026-07-04",
        "status": "completed",
        "payment_status": "charged",
        "progress": {
          "total_services": 2,
          "completed_services": 2,
          "remaining_services": 0,
          "percentage": 100
        },
        "total_price": 300,
        "completed_at": "2026-07-04 15:30:00",
        "auto_accept_at": "2026-07-05 15:30:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 15,
      "total": 1
    }
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
#### Example: Find completed tasks for reports
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
      {
        "id": 501,
        "status": "completed",
        "payment_status": "charged",
        "progress": { "total_services": 2, "completed_services": 2, "remaining_services": 0, "percentage": 100 }
      }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
Use query filters supported by the task list endpoint, such as `status`, `payment_status`, `date_from`, and `date_to`, to narrow report review queues. For example, a frontend can request completed tasks by using `status=completed` as a query parameter.

## Endpoint: Show Task Report
- **Method:** GET
- **URL:** /api/v1/company/tasks/{id}
- **Auth:** Bearer
- **Purpose:** Return task details with services, products, attachments, and worker submissions used to render reports.

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
    "status": "completed",
    "payment_status": "charged",
    "progress": {
      "total_services": 2,
      "completed_services": 2,
      "remaining_services": 0,
      "percentage": 100
    },
    "services": [
      {
        "id": 9001,
        "execution_instructions": "Check shelf availability for all selected SKUs.",
        "unit_price": "150.00",
        "status": "completed",
        "service": {
          "id": 3,
          "key": "on_shelf_availability",
          "name": "On Shelf Availability",
          "submission_form": {
            "fields": {
              "items": {
                "type": "array",
                "required": true
              }
            }
          }
        },
        "products": [
          {
            "id": 7001,
            "product": {
              "id": 10,
              "name": "Acme Cola 330ml",
              "sku": "ACME-COLA-330"
            },
            "product_details": {}
          }
        ],
        "attachments": [],
        "submission": {
          "id": 3001,
          "task_service_id": 9001,
          "worker_id": 44,
          "form_data": {
            "items": [
              {
                "product_id": 10,
                "sku": "ACME-COLA-330",
                "availability": "available"
              }
            ],
            "additional_notes": "All selected SKUs were available."
          },
          "status": "completed",
          "completed_at": "2026-07-04 15:20:00",
          "attachments": [
            {
              "id": 8001,
              "field": "before_picture_files",
              "collection": "submission_files",
              "name": "Shelf before",
              "file_name": "before.jpg",
              "mime_type": "image/jpeg",
              "size": 120000,
              "url": "https://cdn.example.com/submissions/before.jpg"
            }
          ],
          "created_at": "2026-07-04 15:15:00",
          "updated_at": "2026-07-04 15:20:00"
        }
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

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

- **404 — task not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Render on-shelf availability report
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
    "status": "completed",
    "services": [
      {
        "id": 9001,
        "status": "completed",
        "service": { "key": "on_shelf_availability", "name": "On Shelf Availability" },
        "submission": {
          "form_data": {
            "items": [
              { "product_id": 10, "sku": "ACME-COLA-330", "availability": "available" }
            ]
          },
          "attachments": [
            { "field": "before_picture_files", "url": "https://cdn.example.com/submissions/before.jpg" }
          ]
        }
      }
    ]
  }
}
```

### Notes
Report content is service-specific. For `on_shelf_availability`, `form_data.items[]` contains SKU availability values. For `freshness_report`, `form_data.items[]` contains quantities and expiry dates. Display rules should be driven by each service `submission_form` schema.

## Branch: Service report type
**Condition:** The task contains different service types with different report schemas.

### Case: On Shelf Availability
**When:** `services[].service.key` is `on_shelf_availability`.
**Explanation:** Render each `submission.form_data.items[]` row as a SKU availability report with `product_id`, `sku`, and `availability`.

#### Endpoint: Show Task Report
- **Method:** GET
- **URL:** /api/v1/company/tasks/{id}

## Branch: Freshness Report
**Condition:** The task contains freshness reporting services.

### Case: Freshness quantities and expiry dates
**When:** `services[].service.key` is `freshness_report`.
**Explanation:** Render each `submission.form_data.items[]` row as a freshness report with `product_id`, `sku`, `quantity`, and `expiry_date`.

#### Endpoint: Show Task Report
- **Method:** GET
- **URL:** /api/v1/company/tasks/{id}

---
# Flow: Company Report Review Decision

## Description
Company Report Review Decision documents how the company accepts or rejects a completed task after reviewing worker report submissions.

## Business Goal
Close the loop between report review and task lifecycle by allowing companies to approve correct report output or reject incomplete/incorrect report submissions with a reason.

## Module Overview
This flow reuses existing task review endpoints. Reports are reviewed through task details, then task state is changed through accept or reject endpoints under `/api/v1/company/tasks/{id}`.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_task` permission.
- Task report data has been reviewed through `Show Task Report`.
- Rejection reason must be a string between 10 and 2000 characters.

## Walkthrough
1. Call `Show Task Report` and render worker submissions.
2. If all report data is acceptable, call `Accept Reported Task`.
3. If report data is incomplete or incorrect, call `Reject Reported Task` with a reason.
4. For rejected tasks, use review messages from the task review flow to discuss corrections.

## Endpoint: Accept Reported Task
- **Method:** PATCH
- **URL:** /api/v1/company/tasks/{id}/accept
- **Auth:** Bearer
- **Purpose:** Accept a completed task after reviewing its report submissions.

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
    "company_accepted_at": "2026-07-05 12:00:00"
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
#### Example: Accept report output
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
Accepting the task confirms the report output and closes the company review step.

## Endpoint: Reject Reported Task
- **Method:** POST
- **URL:** /api/v1/company/tasks/{id}/reject
- **Auth:** Bearer
- **Purpose:** Reject a completed task report with a required reason.

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
    "rejection_reason": "Freshness report is missing expiry dates for two SKUs."
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
  "message": "The reason field is required.",
  "errors": {
    "reason": ["The reason field is required."]
  }
}
```

### Examples
#### Example: Reject report output
Request:
```json
{
  "reason": "Freshness report is missing expiry dates for two SKUs."
}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 501,
    "status": "rejected",
    "rejection_reason": "Freshness report is missing expiry dates for two SKUs."
  }
}
```

### Notes
Rejecting a report puts the task in `rejected` status, which enables review messages for follow-up.

## Branch: Report accepted or rejected
**Condition:** The company finished reviewing submitted report data.

### Case: Accept or reject based on report quality
**When:** Report submissions are complete or incomplete.
**Explanation:** Accept correct report output. Reject incomplete report output with a clear reason that can be used by admins and workers for follow-up.

#### Endpoint: Reject Reported Task
- **Method:** POST
- **URL:** /api/v1/company/tasks/{id}/reject
