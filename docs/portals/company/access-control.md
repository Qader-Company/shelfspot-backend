# Flow: Company Role Management

## Description
Company Role Management documents how a company user with role-management permissions lists the company permission catalog, lists company-scoped roles, creates roles, updates roles, and deletes roles.

## Business Goal
Allow each company to define internal access levels for its own admins without mixing roles or permissions with other companies or other portals.

## Module Overview
This flow belongs to the company portal Access Control module. All endpoints are under `/api/v1/company/access-control`, require an authenticated company Bearer token, require company tenant resolution through `X-Company-Slug`, and apply permission middleware for each action.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Company-Slug` for the company tenant context.
- Client sends `X-Authorization` with the platform API key.
- The acting company user has the required permission for the action, such as `view_role`, `create_role`, `edit_role`, or `delete_role`.
- Role permissions must be valid company portal permission names.

## Walkthrough
1. Call `List Permissions` to load the company permission catalog that can be assigned to roles.
2. Call `List Roles` to display existing company-scoped roles.
3. Call `Create Role` with a role name and optional permission names.
4. Call `Update Role` when a role name or permission list must change.
5. Call `Delete Role` when the company no longer needs that role.

## Endpoint: List Permissions
- **Method:** GET
- **URL:** /api/v1/company/access-control/permissions
- **Auth:** Bearer
- **Purpose:** Return available company portal permissions for the current tenant company.

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
  "data": [
    {
      "id": 1,
      "name": "view_role",
      "label": "View roles",
      "portal": "company"
    },
    {
      "id": 2,
      "name": "create_role",
      "label": "Create roles",
      "portal": "company"
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
#### Example: Load permissions before building role form
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "view_product", "label": "View products", "portal": "company" },
    { "id": 2, "name": "create_task", "label": "Create tasks", "portal": "company" }
  ]
}
```

### Notes
This endpoint requires the acting user to have `view_role`. Use the returned `name` values in role create/update requests.

## Endpoint: List Roles
- **Method:** GET
- **URL:** /api/v1/company/access-control/roles
- **Auth:** Bearer
- **Purpose:** Return roles scoped to the current company tenant.

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
  "data": [
    {
      "id": 10,
      "name": "catalog_manager",
      "portal": "company",
      "company_id": 5,
      "permissions": [
        { "id": 21, "name": "view_product", "label": "View products", "portal": "company" },
        { "id": 22, "name": "create_product", "label": "Create products", "portal": "company" }
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
#### Example: List company roles
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
      "id": 10,
      "name": "catalog_manager",
      "portal": "company",
      "company_id": 5,
      "permissions": [
        { "id": 21, "name": "view_product", "label": "View products", "portal": "company" }
      ]
    }
  ]
}
```

### Notes
The response only includes roles for the current tenant company. The route requires `view_role`.

## Endpoint: Create Role
- **Method:** POST
- **URL:** /api/v1/company/access-control/roles
- **Auth:** Bearer
- **Purpose:** Create a new role for the current company and optionally attach company permissions.

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
  "name": "string (required, max:255)",
  "permissions": ["string (optional, each value must be a valid company permission name)"]
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Created successfully.",
  "data": {
    "id": 11,
    "name": "task_supervisor",
    "portal": "company",
    "company_id": 5,
    "permissions": [
      { "id": 31, "name": "view_task", "label": "View tasks", "portal": "company" },
      { "id": 32, "name": "edit_task", "label": "Edit tasks", "portal": "company" }
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

- **422 — validation error**
```json
{
  "message": "The selected permissions.0 is invalid.",
  "errors": {
    "permissions.0": ["The selected permissions.0 is invalid."]
  }
}
```

### Examples
#### Example: Create task supervisor role
Request:
```json
{
  "name": "task_supervisor",
  "permissions": ["view_task", "create_task", "edit_task"]
}
```
Response:
```json
{
  "success": true,
  "message": "Created successfully.",
  "data": {
    "id": 11,
    "name": "task_supervisor",
    "portal": "company",
    "company_id": 5,
    "permissions": [
      { "id": 31, "name": "view_task", "label": "View tasks", "portal": "company" },
      { "id": 32, "name": "create_task", "label": "Create tasks", "portal": "company" },
      { "id": 33, "name": "edit_task", "label": "Edit tasks", "portal": "company" }
    ]
  }
}
```

### Notes
This endpoint requires `create_role`. Do not send admin portal permission names; validation accepts only company portal permission names.

## Endpoint: Update Role
- **Method:** PATCH
- **URL:** /api/v1/company/access-control/roles/{roleId}
- **Auth:** Bearer
- **Purpose:** Update a company role name and/or replace its assigned permissions.

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
  "name": "string (optional, max:255)",
  "permissions": ["string (optional, each value must be a valid company permission name)"]
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 11,
    "name": "senior_task_supervisor",
    "portal": "company",
    "company_id": 5,
    "permissions": [
      { "id": 31, "name": "view_task", "label": "View tasks", "portal": "company" },
      { "id": 34, "name": "delete_task", "label": "Delete tasks", "portal": "company" }
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

- **404 — role not found**
```json
{ "success": false, "message": "Not found." }
```

- **422 — validation error**
```json
{
  "message": "The name field must be a string.",
  "errors": {
    "name": ["The name field must be a string."]
  }
}
```

### Examples
#### Example: Rename role and replace permissions
Request:
```json
{
  "name": "senior_task_supervisor",
  "permissions": ["view_task", "edit_task", "delete_task"]
}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 11,
    "name": "senior_task_supervisor",
    "portal": "company",
    "company_id": 5,
    "permissions": [
      { "id": 31, "name": "view_task", "label": "View tasks", "portal": "company" },
      { "id": 33, "name": "edit_task", "label": "Edit tasks", "portal": "company" },
      { "id": 34, "name": "delete_task", "label": "Delete tasks", "portal": "company" }
    ]
  }
}
```

### Notes
The route supports both `PUT` and `PATCH`; prefer `PATCH` for partial updates. This endpoint requires `edit_role`.

## Endpoint: Delete Role
- **Method:** DELETE
- **URL:** /api/v1/company/access-control/roles/{roleId}
- **Auth:** Bearer
- **Purpose:** Delete a role scoped to the current company.

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

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

- **404 — role not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Delete unused role
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
This endpoint requires `delete_role`. Delete only roles that are no longer assigned to active company admins.

## Branch: Role permissions changed
**Condition:** The company changes the responsibilities assigned to an internal admin role.

### Case: Replace role permission set
**When:** A role should keep the same identity but receive a new list of allowed actions.
**Explanation:** Load available permissions, submit the full desired `permissions` array to `Update Role`, then reload roles to confirm the new assignments.

#### Endpoint: Update Role
- **Method:** PATCH
- **URL:** /api/v1/company/access-control/roles/{roleId}

---
# Flow: Company Admin Management

## Description
Company Admin Management documents how a company user with admin-management permissions lists company admins, creates a new company admin, updates company admin profile/access data, and deletes company admins.

## Business Goal
Allow a company owner or authorized company admin to manage internal users who can operate the company portal while keeping those users scoped to the current company tenant.

## Module Overview
This flow belongs to the company portal Company Admins and Access Control modules. The endpoints are under `/api/v1/company/access-control/admins`, use the current tenant company from `X-Company-Slug`, and assign roles by company-scoped role names.

## Prerequisites
- Client has a valid company Bearer access token.
- Client sends `X-Company-Slug` for tenant context.
- Client sends `X-Authorization` with the platform API key.
- The acting company user has the required permission: `view_admin`, `create_admin`, `edit_admin`, or `delete_admin`.
- Roles assigned to admins already exist for the current company.

## Walkthrough
1. Call `List Admins` to show company users with their active status, owner flag, and roles.
2. Call `Create Admin` with name, email, password, optional active flag, and optional roles.
3. Call `Update Admin` to edit profile fields, active status, password, or assigned role names.
4. Call `Delete Admin` to remove a company admin from the current company tenant.

## Endpoint: List Admins
- **Method:** GET
- **URL:** /api/v1/company/access-control/admins
- **Auth:** Bearer
- **Purpose:** Return company admins for the current tenant company.

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
  "data": [
    {
      "id": 15,
      "name": "Company Owner",
      "email": "owner@example.com",
      "is_active": true,
      "is_owner": true,
      "roles": ["full_access"]
    },
    {
      "id": 16,
      "name": "Task Manager",
      "email": "task.manager@example.com",
      "is_active": true,
      "is_owner": false,
      "roles": ["task_supervisor"]
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
#### Example: List company admins
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
      "id": 16,
      "name": "Task Manager",
      "email": "task.manager@example.com",
      "is_active": true,
      "is_owner": false,
      "roles": ["task_supervisor"]
    }
  ]
}
```

### Notes
This endpoint requires `view_admin`. The `roles` array contains role names, not role IDs.

## Endpoint: Create Admin
- **Method:** POST
- **URL:** /api/v1/company/access-control/admins
- **Auth:** Bearer
- **Purpose:** Create a new company admin in the current company tenant and assign optional roles.

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
  "name": "string (required, max:255)",
  "email": "string (required, valid email, unique in users)",
  "password": "string (required, min:8)",
  "is_active": "boolean (optional)",
  "roles": ["string (optional, each value must be an existing company role name for this company)"]
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Created successfully.",
  "data": {
    "id": 17,
    "name": "Catalog Admin",
    "email": "catalog.admin@example.com",
    "is_active": true,
    "is_owner": false,
    "roles": ["catalog_manager"]
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
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."],
    "roles.0": ["The selected roles.0 is invalid."]
  }
}
```

### Examples
#### Example: Create catalog admin
Request:
```json
{
  "name": "Catalog Admin",
  "email": "catalog.admin@example.com",
  "password": "StrongPass1",
  "is_active": true,
  "roles": ["catalog_manager"]
}
```
Response:
```json
{
  "success": true,
  "message": "Created successfully.",
  "data": {
    "id": 17,
    "name": "Catalog Admin",
    "email": "catalog.admin@example.com",
    "is_active": true,
    "is_owner": false,
    "roles": ["catalog_manager"]
  }
}
```

### Notes
This endpoint requires `create_admin`. Role names are validated against roles with `portal = company` and the current tenant `company_id`.

## Endpoint: Update Admin
- **Method:** PATCH
- **URL:** /api/v1/company/access-control/admins/{user}
- **Auth:** Bearer
- **Purpose:** Update an existing company admin profile, active state, password, or assigned roles.

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
  "name": "string (optional, max:255)",
  "email": "string (optional, valid email, unique for company users except this user)",
  "password": "string (optional, min:8)",
  "is_active": "boolean (optional)",
  "roles": ["string (optional, each value must be an existing company role name for this company)"]
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 17,
    "name": "Senior Catalog Admin",
    "email": "catalog.admin@example.com",
    "is_active": true,
    "is_owner": false,
    "roles": ["catalog_manager", "task_supervisor"]
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

- **404 — admin not found**
```json
{ "success": false, "message": "Not found." }
```

- **422 — validation error**
```json
{
  "message": "The selected roles.0 is invalid.",
  "errors": {
    "roles.0": ["The selected roles.0 is invalid."]
  }
}
```

### Examples
#### Example: Update admin roles and name
Request:
```json
{
  "name": "Senior Catalog Admin",
  "roles": ["catalog_manager", "task_supervisor"]
}
```
Response:
```json
{
  "success": true,
  "message": "Updated successfully.",
  "data": {
    "id": 17,
    "name": "Senior Catalog Admin",
    "email": "catalog.admin@example.com",
    "is_active": true,
    "is_owner": false,
    "roles": ["catalog_manager", "task_supervisor"]
  }
}
```

### Notes
The route supports both `PUT` and `PATCH`; prefer `PATCH` for partial updates. This endpoint requires `edit_admin`.

## Endpoint: Delete Admin
- **Method:** DELETE
- **URL:** /api/v1/company/access-control/admins/{user}
- **Auth:** Bearer
- **Purpose:** Delete a company admin from the current tenant company.

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

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

- **404 — admin not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Delete company admin
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
This endpoint requires `delete_admin`. The `{user}` route parameter is the user ID of the company admin.

## Branch: Assign role to company admin
**Condition:** A company admin needs a different permission set.

### Case: Update admin roles
**When:** The role already exists for the current company.
**Explanation:** Submit the full desired role-name array to `Update Admin`; role names are validated against current-company roles only.

#### Endpoint: Update Admin
- **Method:** PATCH
- **URL:** /api/v1/company/access-control/admins/{user}
