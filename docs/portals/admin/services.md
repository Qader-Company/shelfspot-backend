# Flow: Admin Service Management

## Description
Admin Service Management documents how ShelfSpot admins list available service definitions, open a service by key, and update service price, activation, and translations.

## Business Goal
Allow platform admins to control the service catalog that companies use when creating tasks, including pricing and localized descriptions.

## Module Overview
This flow belongs to the admin portal Services module. The admin routes are under `/api/v1/admin/services` and require an admin Bearer token. The same Service controller also serves company service discovery, but admin users can see inactive services and can update service configuration.

## Prerequisites
- `baseUrl` points to the API host.
- Client has a valid platform API key for `X-Authorization`.
- Client has an admin access token with admin portal access ability.
- Acting admin has the required permission: `view_service` for read endpoints and `edit_service` for update.
- Service `id` in the route is treated as the service key by the current controller show method.

## Walkthrough
1. Admin opens the service catalog management screen.
2. Client calls `List Admin Services` to show service definitions.
3. Admin opens a service using `Show Admin Service`.
4. Admin edits price, active state, or translations.
5. Client submits changes through `Update Admin Service`.

## Endpoint: List Admin Services
- **Method:** GET
- **URL:** /api/v1/admin/services
- **Auth:** Bearer
- **Purpose:** Return service definitions for admin review and management.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{admin_access_token}}
```

### Parameters
- **active** (query, optional, boolean) — filter services by active state.

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
      "description": "Ensure products are displayed on shelf according to planogram.",
      "price": "50.00",
      "is_active": true,
      "request_form": {
        "fields": {
          "planogram_files": {
            "type": "array<file>",
            "required": true
          }
        },
        "requires_products": true
      },
      "submission_form": {
        "fields": {
          "before_picture_files": {
            "type": "array<file>",
            "required": true
          },
          "after_picture_files": {
            "type": "array<file>",
            "required": true
          }
        }
      }
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
#### Example: List active and inactive services
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
      "price": "50.00",
      "is_active": true
    }
  ]
}
```

### Notes
Unlike company service discovery, admin listing is not forced to active services only. Admins can use the `active` query filter when needed.

## Endpoint: Show Admin Service
- **Method:** GET
- **URL:** /api/v1/admin/services/{id}
- **Auth:** Bearer
- **Purpose:** Return one service definition, including translations.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{admin_access_token}}
```

### Parameters
- **id** (path, required, string) — service key used by the current controller lookup, for example `primary_display`.

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
    "description": "Ensure products are displayed on shelf according to planogram.",
    "price": "50.00",
    "is_active": true,
    "request_form": {
      "fields": {
        "planogram_files": {
          "type": "array<file>",
          "required": true
        }
      },
      "requires_products": true
    },
    "submission_form": {
      "fields": {
        "before_picture_files": {
          "type": "array<file>",
          "required": true
        },
        "after_picture_files": {
          "type": "array<file>",
          "required": true
        }
      }
    },
    "translations": {
      "en": {
        "description": "Ensure products are displayed on shelf according to planogram."
      },
      "ar": {
        "description": "التأكد من عرض المنتجات على الرف الأساسي حسب البلانوجرام."
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
#### Example: Show primary display service
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
    "price": "50.00",
    "is_active": true,
    "translations": {
      "en": { "description": "Ensure products are displayed on shelf according to planogram." }
    }
  }
}
```

### Notes
The route parameter is named `{id}`, but the controller show method resolves the service by key.

## Endpoint: Update Admin Service
- **Method:** PATCH
- **URL:** /api/v1/admin/services/{id}
- **Auth:** Bearer
- **Purpose:** Update service translations, price, or active state.

### Headers
```
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{admin_access_token}}
```

### Parameters
- **id** (path, required, string) — service ID used by the update controller lookup.

### Request Body
```json
{
  "translations": {
    "en": {
      "description": "string (optional, max:255)"
    },
    "ar": {
      "description": "string (optional, max:255)"
    }
  },
  "price": "number (optional, min:0)",
  "is_active": "boolean (optional)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Updated successfully."
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

- **422 — validation error**
```json
{
  "message": "The price field must be at least 0.",
  "errors": {
    "price": ["The price field must be at least 0."],
    "translations.en.description": ["The translations.en.description field must not be greater than 255 characters."]
  }
}
```

### Examples
#### Example: Update service price and translation
Request:
```json
{
  "price": 75,
  "is_active": true,
  "translations": {
    "en": { "description": "Updated admin service description." }
  }
}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully."
}
```

### Notes
The route supports both `PUT` and `PATCH`; prefer `PATCH` for partial updates. Updating `price` affects the price applied to future company tasks.

## Branch: Service disabled by admin
**Condition:** Admin sets `is_active` to `false` for a service.

### Case: Hide service from company task creation
**When:** `is_active = false`.
**Explanation:** Company service discovery and task creation should no longer treat that service as selectable or valid for new tasks.

#### Endpoint: Update Admin Service
- **Method:** PATCH
- **URL:** /api/v1/admin/services/{id}
