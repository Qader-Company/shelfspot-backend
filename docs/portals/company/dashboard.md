# Flow: Company Dashboard Statistics

## Description
Company Dashboard Statistics documents the `/dashboard` route used by the company portal to load the main statistics screen. This route is intended to return aggregated company metrics in one response instead of forcing the frontend to call tasks, wallets, catalog, and service endpoints separately.

## Business Goal
Give the company dashboard a single API contract for high-level business indicators such as task counts, task lifecycle status totals, wallet balance, catalog totals, and recent activity summaries.

## Module Overview
This flow belongs to the company portal Dashboard area. The endpoint is company-scoped, requires the normal company authentication and tenant headers, and should only return statistics for the company resolved by `X-Company-Slug`.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- The acting company user has permission to view dashboard/statistics data according to backend policy.
- Dashboard numbers must be calculated for the current tenant company only.

## Walkthrough
1. Company user opens the dashboard screen.
2. Client calls `Get Company Dashboard Statistics` once after authentication and tenant selection.
3. Backend resolves the current company from `X-Company-Slug`.
4. Backend returns grouped statistics for tasks, wallet, catalog, and recent activity.
5. Frontend renders statistic cards, charts, and recent activity widgets from the response.
6. Frontend refreshes the same endpoint after actions that change statistics, such as task payment, task acceptance, wallet recharge, or catalog updates.

## Endpoint: Get Company Dashboard Statistics
- **Method:** GET
- **URL:** /api/v1/company/dashboard
- **Auth:** Bearer
- **Purpose:** Return aggregated dashboard statistics for the current company tenant.

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
    "tasks": {
      "total": 128,
      "draft": 6,
      "pending": 18,
      "started": 4,
      "in_progress": 12,
      "completed": 9,
      "accepted": 74,
      "rejected": 3,
      "reopened": 1,
      "failed": 1,
      "refund_requested": 0
    },
    "payments": {
      "pending": 6,
      "charged": 119,
      "refunded": 2,
      "failed": 1
    },
    "wallet": {
      "balance": 1250.75,
      "last_transaction": {
        "id": 101,
        "type": "coupon_redemption",
        "amount": "250.00",
        "balance_after": "1250.75",
        "created_at": "2026-07-05"
      }
    },
    "catalog": {
      "brands": 12,
      "sub_brands": 24,
      "categories": 18,
      "sub_categories": 42,
      "products": 320,
      "active_products": 300,
      "inactive_products": 20
    },
    "services": {
      "available": 5
    },
    "recent_tasks": [
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

- **404 — company tenant not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Load dashboard statistics
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "tasks": {
      "total": 128,
      "pending": 18,
      "in_progress": 12,
      "completed": 9,
      "accepted": 74
    },
    "wallet": {
      "balance": 1250.75
    },
    "catalog": {
      "brands": 12,
      "products": 320,
      "active_products": 300
    },
    "recent_tasks": [
      {
        "id": 501,
        "status": "pending",
        "payment_status": "charged",
        "total_price": 300
      }
    ]
  }
}
```

### Notes
The dashboard response should be treated as read-only aggregated data. The frontend should not calculate tenant-wide totals by combining data from other pages when this route is available. If the backend adds or removes dashboard cards, keep the response grouped by module (`tasks`, `payments`, `wallet`, `catalog`, `services`, `recent_tasks`) so UI widgets remain easy to map.

## Branch: Empty Company Data
**Condition:** The company has no tasks, no products, and no wallet transactions yet.

### Case: First dashboard load for a new company
**When:** The company account is new or has not created operational data.
**Explanation:** Backend should return zero counts and empty arrays instead of omitting sections, so the frontend can render empty-state widgets consistently.

#### Endpoint: Get Company Dashboard Statistics
- **Method:** GET
- **URL:** /api/v1/company/dashboard
