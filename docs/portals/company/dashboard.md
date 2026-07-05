# Flow: Company Dashboard

## Description
Company Dashboard documents the current backend status for the requested `/dashboard` company route and the available company endpoints that can be used to assemble dashboard data today. A code search of the current repository does not show an implemented `/api/v1/company/dashboard` route, controller, or dashboard module.

## Business Goal
Give the company portal a clear dashboard integration note so frontend and integrator teams know whether to call a single dashboard endpoint or compose dashboard widgets from existing company APIs.

## Module Overview
In the current implementation, company dashboard data is distributed across existing company modules: tasks, wallets, products, services, and catalog endpoints. The requested `/dashboard` route is not registered in the current company route files, so this document does not invent a standalone dashboard endpoint contract.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- Client has the permissions required by each source endpoint, such as `view_task`, `view_wallet`, `view_product`, or `view_service`.
- Backend must add a real `/api/v1/company/dashboard` route before clients can call it as a single endpoint.

## Walkthrough
1. Check whether `/api/v1/company/dashboard` exists in the active backend build.
2. If the route is not implemented, compose dashboard widgets from existing company endpoints.
3. Use `List Tasks for Dashboard` for task status, dates, payment status, and progress widgets.
4. Use `Wallet Summary for Dashboard` for current wallet balance and recent wallet transactions.
5. Use `Products for Dashboard` for product catalog list/count widgets.
6. Replace this composed approach with a single dashboard endpoint only after the backend route is implemented.

## Endpoint: List Tasks for Dashboard
- **Method:** GET
- **URL:** /api/v1/company/tasks
- **Auth:** Bearer
- **Purpose:** Provide task list data that can be summarized into dashboard widgets such as active tasks, completed tasks, payment status, and progress.

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
        "date": "2026-07-05",
        "status": "pending",
        "payment_status": "charged",
        "total_price": 300,
        "progress": {
          "total_services": 2,
          "completed_services": 1,
          "remaining_services": 1,
          "percentage": 50
        }
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
#### Example: Load recent tasks widget
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
This is not a dashboard-specific endpoint. It is the existing task list endpoint and accepts task filters such as `status`, `payment_status`, `date_from`, and `date_to`.

## Endpoint: Wallet Summary for Dashboard
- **Method:** GET
- **URL:** /api/v1/company/wallets
- **Auth:** Bearer
- **Purpose:** Provide current wallet balance and recent transactions for dashboard wallet cards.

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
    "balance": 1250.75,
    "transactions": {
      "data": [
        {
          "id": 101,
          "type": "coupon_redemption",
          "amount": "250.00",
          "balance_after": "1250.75",
          "created_at": "2026-07-05"
        }
      ],
      "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 1
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

### Examples
#### Example: Load wallet dashboard card
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "balance": 1250.75,
    "transactions": {
      "data": [{ "id": 101, "type": "coupon_redemption", "amount": "250.00" }],
      "meta": { "current_page": 1, "per_page": 15, "total": 1 }
    }
  }
}
```

### Notes
This is the existing wallet endpoint. It accepts a `type` query filter for transaction type.

## Endpoint: Products for Dashboard
- **Method:** GET
- **URL:** /api/v1/company/products
- **Auth:** Bearer
- **Purpose:** Provide product catalog data that can be summarized into dashboard product widgets.

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
        "id": 10,
        "name": "Acme Cola 330ml",
        "sku": "ACME-COLA-330",
        "active": true
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
#### Example: Load product count source
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "data": [{ "id": 10, "name": "Acme Cola 330ml", "active": true }],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
This is the existing product list endpoint. Dashboard clients can use `meta.total` for a product count when no dedicated dashboard endpoint exists.

## Branch: Requested /dashboard route is missing
**Condition:** Frontend attempts to call `/api/v1/company/dashboard`, but the backend build does not register that route.

### Case: Compose dashboard from existing endpoints
**When:** The route returns 404 or is absent from route registration.
**Explanation:** Use the existing tasks, wallets, products, services, and catalog endpoints to build dashboard widgets until a real dashboard endpoint is implemented.

#### Endpoint: List Tasks for Dashboard
- **Method:** GET
- **URL:** /api/v1/company/tasks
