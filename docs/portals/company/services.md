# Flow: Company Service Discovery

## Description
Company Service Discovery documents how a company user lists active ShelfSpot services and opens a specific service by key to read pricing, execution-time minimums, request form schema, submission form schema, and translations.

## Business Goal
Allow the company portal to show which services can be selected during task creation, including the minimum price and minimum execution time that the frontend must respect later in task flows.

## Module Overview
This flow belongs to the company portal Services module. Endpoints are under `/api/v1/company/services`, require an authenticated company Bearer token, and require the `view_service` permission. For company users, the backend forces the service list to active services only.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- The acting company user has the `view_service` permission.
- Client uses the service `key` returned from the list endpoint when requesting details.

## Walkthrough
1. Call `List Services` when rendering the company task/service selection screen.
2. Read each service `key`, `minimum_price`, `minimum_execution_time`, `request_form`, and `submission_form` from the response.
3. Call `Show Service` with a selected service key when the UI needs detailed translated descriptions.
4. Use the returned `request_form` to build the company-side task request form for that service.
5. Use the returned minimum values as constraints when pricing or scheduling a task that includes the service.

## Endpoint: List Services
- **Method:** GET
- **URL:** /api/v1/company/services
- **Auth:** Bearer
- **Purpose:** Return active services visible to company users.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "key": "primary_display",
      "name": "Primary Display",
      "description": "Main display execution service.",
      "minimum_price": "100.00",
      "minimum_execution_time": 60,
      "is_active": true,
      "request_form": [
        {
          "name": "display_location",
          "type": "text",
          "required": true
        }
      ],
      "submission_form": [
        {
          "name": "photo",
          "type": "image",
          "required": true
        }
      ]
    }
  ]
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
#### Example: Load services for task creation
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "key": "primary_display",
      "name": "Primary Display",
      "description": "Main display execution service.",
      "minimum_price": "100.00",
      "minimum_execution_time": 60,
      "is_active": true,
      "request_form": [],
      "submission_form": []
    },
    {
      "id": 2,
      "key": "on_shelf_availability",
      "name": "On Shelf Availability",
      "description": "Availability checking service.",
      "minimum_price": "50.00",
      "minimum_execution_time": 30,
      "is_active": true,
      "request_form": [],
      "submission_form": []
    }
  ]
}
```

### Notes
The endpoint accepts an `active` filter in the controller, but for non-admin users the backend overrides the filter to active services only. Company clients should treat the returned services as selectable services.

## Endpoint: Show Service
- **Method:** GET
- **URL:** /api/v1/company/services/{key}
- **Auth:** Bearer
- **Purpose:** Return one service by service key, including translations.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
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
    "id": 1,
    "key": "primary_display",
    "name": "Primary Display",
    "description": "Main display execution service.",
    "minimum_price": "100.00",
    "minimum_execution_time": 60,
    "is_active": true,
    "request_form": [
      {
        "name": "display_location",
        "type": "text",
        "required": true
      }
    ],
    "submission_form": [
      {
        "name": "photo",
        "type": "image",
        "required": true
      }
    ],
    "translations": {
      "en": {
        "description": "Main display execution service."
      },
      "ar": {
        "description": "خدمة تنفيذ العرض الرئيسي."
      }
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

- **404 — service not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Open primary display service
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "key": "primary_display",
    "name": "Primary Display",
    "description": "Main display execution service.",
    "minimum_price": "100.00",
    "minimum_execution_time": 60,
    "is_active": true,
    "request_form": [],
    "submission_form": [],
    "translations": {
      "en": {
        "description": "Main display execution service."
      },
      "ar": {
        "description": "خدمة تنفيذ العرض الرئيسي."
      }
    }
  }
}
```

### Notes
The `{key}` path value is the service key, not the numeric service ID. Supported service keys include `primary_display`, `secondary_display_execution`, `on_shelf_availability`, `instore_visibility`, and `freshness_report`.

## Branch: Service selected for task creation
**Condition:** A company user selects a service from the service list while creating a task.

### Case: Load selected service details
**When:** The UI needs translated descriptions or a complete form schema for the selected service.
**Explanation:** Use the selected service `key` from `List Services` to call `Show Service`, then render the returned `request_form` and enforce `minimum_price` and `minimum_execution_time` in the task creation UI.

#### Endpoint: Show Service
- **Method:** GET
- **URL:** /api/v1/company/services/{key}
