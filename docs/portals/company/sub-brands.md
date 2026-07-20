# Flow: Company Sub-Brand Management

## Description
Company Sub-Brand Management documents how company users list, create, view, update, delete, and bulk-delete sub-brands inside the current company tenant.

## Business Goal
Allow a company to maintain its own sub-brand catalog so products, categories, and tasks can reference clean company-scoped child catalog data under a parent brand.

## Module Overview
This flow belongs to the company portal Sub-Brands module. Endpoints are under `/api/v1/company/sub-brands`, require a company Bearer token, resolve tenant data through `X-Company-Slug`, and enforce sub-brand permissions per action.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- The acting company user has the required permission: `view_sub_brand`, `create_sub_brand`, `edit_sub_brand`, or `delete_sub_brand`.
- `brand_id` must reference a brand in the current company.
- Logo uploads must be image files with one of: `jpeg`, `png`, `jpg`, `gif`, `svg`, max `2048 KB`; logo is required on create.

## Walkthrough
1. Call `List Sub-Brands` to show paginated company sub-brands and optional filters.
2. Call `Create Sub-Brand` with a parent `brand_id`, sub-brand name, required logo, and active flag.
3. Call `Show Sub-Brand` to open one sub-brand by ID.
4. Call `Update Sub-Brand` to edit name, logo, or active state.
5. Call `Delete Sub-Brand` for a single soft-delete, or `Bulk Delete Sub-Brands` for multiple IDs.

## Endpoint: List Sub-Brands
- **Method:** GET
- **URL:** /api/v1/company/sub-brands
- **Auth:** Bearer
- **Purpose:** Return paginated sub-brands scoped to the current company.

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
        "name": "Acme Mini",
        "logo": "https://cdn.example.com/sub-brands/acme-mini.png",
        "active": true
      }
    ],
    "links": {
      "first": "http://localhost/api/v1/company/sub-brands?page=1",
      "last": "http://localhost/api/v1/company/sub-brands?page=1",
      "prev": null,
      "next": null
    },
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
#### Example: List active sub-brands by name
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
      { "id": 1, "name": "Acme Mini", "logo": "https://cdn.example.com/sub-brands/acme-mini.png", "active": true }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
The controller accepts `name`, `active`, and `brand_id` filters from the query string. Results are tenant-scoped by the current company.

## Endpoint: Create Sub-Brand
- **Method:** POST
- **URL:** /api/v1/company/sub-brands
- **Auth:** Bearer
- **Purpose:** Create a new sub-brand for the current company.

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
  "brand_id": "integer (required, must exist in current company brands)",
  "name": "string (required, max:255)",
  "logo": "file (required, image, mimes:jpeg,png,jpg,gif,svg, max:2048KB)",
  "is_active": "boolean (required)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Created successfully."
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
  "message": "The name field is required.",
  "errors": {
    "brand_id": ["The selected brand is invalid."],
    "name": ["The name field is required."],
    "logo": ["The logo field is required."]
  }
}
```

### Examples
#### Example: Create active sub-brand
Request:
```json
{
  "brand_id": 1,
  "name": "Acme Mini",
  "logo": "acme-mini.png",
  "is_active": true
}
```
Response:
```json
{
  "success": true,
  "message": "Created successfully."
}
```

### Notes
Sub-brand creation automatically attaches the current tenant company ID, validates that `brand_id` belongs to the same company, and stores the required logo in the single-file `logo` media collection.

## Endpoint: Show Sub-Brand
- **Method:** GET
- **URL:** /api/v1/company/sub-brands/{id}
- **Auth:** Bearer
- **Purpose:** Return one company sub-brand by ID.

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
    "id": 1,
    "name": "Acme Mini",
    "logo": "https://cdn.example.com/sub-brands/acme-mini.png",
    "active": true
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

- **404 — sub-brand not found**
```json
{ "success": false, "message": "Sub-brand not found." }
```

### Examples
#### Example: Open sub-brand details
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
    "name": "Acme Mini",
    "logo": "https://cdn.example.com/sub-brands/acme-mini.png",
    "active": true
  }
}
```

### Notes
The `{id}` must belong to the current company because sub-brands use company tenant scoping. The list endpoint loads the parent `brand` relation.

## Endpoint: Update Sub-Brand
- **Method:** PATCH
- **URL:** /api/v1/company/sub-brands/{id}
- **Auth:** Bearer
- **Purpose:** Update a sub-brand name, logo, or active state.

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
  "brand_id": "integer (optional, must exist in current company brands)",
  "name": "string (optional, max:255)",
  "logo": "file (optional, image, mimes:jpeg,png,jpg,gif,svg, max:2048KB)",
  "logo_action": "keep | remove | replace (optional)",
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

- **404 — sub-brand not found**
```json
{ "success": false, "message": "Sub-brand not found." }
```

- **422 — validation error**
```json
{
  "message": "The logo field must be a file of type: jpeg, png, jpg, gif, svg.",
  "errors": {
    "logo": ["The logo field must be a file of type: jpeg, png, jpg, gif, svg."]
  }
}
```

### Examples
#### Example: Deactivate sub-brand
Request:
```json
{
  "is_active": false
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
The route supports both `PUT` and `PATCH`; prefer `PATCH` for partial updates. Omit both logo fields to keep the current logo. Use `logo_action=remove` to delete it, or `logo_action=replace` with a new `logo` file to replace it. A new logo without an action remains supported and replaces the current logo.

## Endpoint: Delete Sub-Brand
- **Method:** DELETE
- **URL:** /api/v1/company/sub-brands/{id}
- **Auth:** Bearer
- **Purpose:** Soft-delete a sub-brand and queue cascading catalog delete behavior for its children.

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
  "message": "Delete queued."
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

- **404 — sub-brand not found**
```json
{ "success": false, "message": "Sub-brand not found." }
```

### Examples
#### Example: Delete one sub-brand
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Delete queued."
}
```

### Notes
The delete endpoint returns an accepted queued-delete message in the controller. Related categories, sub-categories, and products are handled through cascade trash actions.

## Endpoint: Bulk Delete Sub-Brands
- **Method:** POST
- **URL:** /api/v1/company/sub-brands/bulk-delete
- **Auth:** Bearer
- **Purpose:** Soft-delete multiple sub-brands by ID.

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
  "ids": ["integer (required, min:1 item, max:100 items, each distinct)"]
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Bulk delete queued."
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
  "message": "The ids field is required.",
  "errors": {
    "ids": ["The ids field is required."],
    "ids.0": ["The ids.0 field must be an integer."]
  }
}
```

### Examples
#### Example: Bulk delete selected sub-brands
Request:
```json
{
  "ids": [1, 2, 3]
}
```
Response:
```json
{
  "success": true,
  "message": "Bulk delete queued."
}
```

### Notes
This endpoint accepts up to 100 distinct IDs per request and requires `delete_sub_brand`.

## Branch: Sub-brand logo replacement
**Condition:** A company updates a sub-brand and sends a new `logo` file.

### Case: Replace single-file logo collection
**When:** The sub-brand already has a logo and the update request includes a new valid image file.
**Explanation:** The backend clears the old `logo` media collection and stores the new file as the sub-brand logo.

#### Endpoint: Update Sub-Brand
- **Method:** PATCH
- **URL:** /api/v1/company/sub-brands/{id}

---
# Flow: Company Sub-Brand Excel Operations

## Description
Company Sub-Brand Excel Operations documents how company users download an import template, export current sub-brands, and import sub-brands from an Excel or CSV file.

## Business Goal
Allow companies to manage sub-brand catalog data in bulk instead of manually creating or updating each sub-brand one by one.

## Module Overview
This flow belongs to the company portal Sub-Brands Excel support. Endpoints are under `/api/v1/company/sub-brands/excel` and reuse company auth, tenant scoping, and sub-brand permissions.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_sub_brand` for template/export and `create_sub_brand` for import.
- Import files must be `xlsx`, `xls`, or `csv`, max `10240 KB`.

## Walkthrough
1. Call `Download Sub-Brand Template` to get the expected spreadsheet structure.
2. Fill sub-brand rows in the template outside the API.
3. Call `Import Sub-Brands` with the completed spreadsheet.
4. Review row-level errors returned by the import response, if any.
5. Call `Export Sub-Brands` whenever the company needs a spreadsheet snapshot of current sub-brand data.

## Endpoint: Download Sub-Brand Template
- **Method:** GET
- **URL:** /api/v1/company/sub-brands/excel/template
- **Auth:** Bearer
- **Purpose:** Download an Excel template for sub-brand import.

### Headers
```
Accept: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
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
  "file": "binary xlsx template response"
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
#### Example: Download sub-brand import template
Request:
```json
{}
```
Response:
```json
{
  "file": "sub-brands-template.xlsx"
}
```

### Notes
This endpoint returns a binary file response rather than the normal JSON API format.

## Endpoint: Export Sub-Brands
- **Method:** GET
- **URL:** /api/v1/company/sub-brands/excel/export
- **Auth:** Bearer
- **Purpose:** Download current company sub-brand data as a spreadsheet.

### Headers
```
Accept: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
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
  "file": "binary xlsx export response"
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
#### Example: Export sub-brand catalog
Request:
```json
{}
```
Response:
```json
{
  "file": "sub-brands-export.xlsx"
}
```

### Notes
This endpoint returns a binary file response and requires `view_sub_brand`.

## Endpoint: Import Sub-Brands
- **Method:** POST
- **URL:** /api/v1/company/sub-brands/excel/import
- **Auth:** Bearer
- **Purpose:** Import sub-brand rows from an uploaded spreadsheet.

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
  "file": "file (required, mimes:xlsx,xls,csv, max:10240KB)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Imported successfully.",
  "data": {
    "created": 10,
    "updated": 2,
    "errors": []
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
  "message": "The file field is required.",
  "errors": {
    "file": ["The file field is required."]
  }
}
```

### Examples
#### Example: Import sub-brand spreadsheet
Request:
```json
{
  "file": "sub-brands.xlsx"
}
```
Response:
```json
{
  "success": true,
  "message": "Imported with row-level validation errors. Please review the errors array.",
  "data": {
    "created": 8,
    "updated": 1,
    "errors": [
      { "row": 5, "errors": { "name": ["The name field is required."] } }
    ]
  }
}
```

### Notes
A successful HTTP response can still include row-level import errors. The client should show the `errors` array when present.

## Branch: Import has row errors
**Condition:** The uploaded file is valid, but one or more rows fail row-level validation.

### Case: Show row-level errors
**When:** The response message says the import completed with validation errors.
**Explanation:** Keep successfully imported rows, display each row error to the user, and let the user fix and re-upload failed rows.

#### Endpoint: Import Sub-Brands
- **Method:** POST
- **URL:** /api/v1/company/sub-brands/excel/import

---
# Flow: Company Sub-Brand Trash Management

## Description
Company Sub-Brand Trash Management documents how company users list deleted sub-brands, restore deleted sub-brands, bulk-restore deleted sub-brands, force-delete one sub-brand, and bulk-force-delete deleted sub-brands.

## Business Goal
Allow companies to recover mistakenly deleted sub-brand catalog data or permanently purge deleted sub-brands when they are no longer needed.

## Module Overview
This flow belongs to the shared catalog trash behavior used by company sub-brands. Endpoints are under `/api/v1/company/sub-brands/trash` and operate only on trashed sub-brand records in the current company tenant.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_sub_brand` for trash list, `edit_sub_brand` for restore, and `delete_sub_brand` for force-delete.
- Bulk actions require an `ids` array with 1 to 100 distinct integer IDs.

## Walkthrough
1. Call `List Trashed Sub-Brands` to show deleted sub-brands.
2. Call `Restore Sub-Brand` to recover one deleted sub-brand.
3. Call `Bulk Restore Sub-Brands` to recover multiple deleted sub-brands.
4. Call `Force Delete Sub-Brand` to permanently delete one trashed sub-brand.
5. Call `Bulk Force Delete Sub-Brands` to permanently delete multiple trashed sub-brands.

## Endpoint: List Trashed Sub-Brands
- **Method:** GET
- **URL:** /api/v1/company/sub-brands/trash
- **Auth:** Bearer
- **Purpose:** Return paginated soft-deleted sub-brands for the current company.

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
        "deleted_at": "2026-07-02T12:00:00.000000Z",
        "purge_status": "pending",
        "name": "Acme Mini",
        "logo": "https://cdn.example.com/sub-brands/acme-mini.png",
        "active": false
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
#### Example: List deleted sub-brands
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
      { "id": 1, "deleted_at": "2026-07-02T12:00:00.000000Z", "purge_status": "pending", "name": "Acme Mini", "logo": "", "active": false }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
This endpoint requires `view_sub_brand` and returns deleted records only.

## Endpoint: Restore Sub-Brand
- **Method:** POST
- **URL:** /api/v1/company/sub-brands/trash/{id}/restore
- **Auth:** Bearer
- **Purpose:** Restore one soft-deleted sub-brand by ID.

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
  "message": "Restored successfully."
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

- **404 — trashed sub-brand not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Restore one sub-brand
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Restored successfully."
}
```

### Notes
This endpoint requires `edit_sub_brand`.

## Endpoint: Bulk Restore Sub-Brands
- **Method:** POST
- **URL:** /api/v1/company/sub-brands/trash/bulk-restore
- **Auth:** Bearer
- **Purpose:** Restore multiple soft-deleted sub-brands by ID.

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
  "ids": ["integer (required, min:1 item, max:100 items, each distinct)"]
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Bulk restored successfully."
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
  "message": "The ids field is required.",
  "errors": {
    "ids": ["The ids field is required."]
  }
}
```

### Examples
#### Example: Restore selected sub-brands
Request:
```json
{
  "ids": [1, 2]
}
```
Response:
```json
{
  "success": true,
  "message": "Bulk restored successfully."
}
```

### Notes
This endpoint requires `edit_sub_brand`.

## Endpoint: Force Delete Sub-Brand
- **Method:** DELETE
- **URL:** /api/v1/company/sub-brands/trash/{id}
- **Auth:** Bearer
- **Purpose:** Permanently delete one trashed sub-brand by ID.

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
  "message": "Force deleted successfully."
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

- **404 — trashed sub-brand not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Permanently delete sub-brand
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Force deleted successfully."
}
```

### Notes
This endpoint requires `delete_sub_brand`. Force delete removes the sub-brand permanently and deletes associated media on force delete.

## Endpoint: Bulk Force Delete Sub-Brands
- **Method:** DELETE
- **URL:** /api/v1/company/sub-brands/trash/bulk-force-delete
- **Auth:** Bearer
- **Purpose:** Permanently delete multiple trashed sub-brands by ID.

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
  "ids": ["integer (required, min:1 item, max:100 items, each distinct)"]
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Bulk force deleted successfully."
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
  "message": "The ids field is required.",
  "errors": {
    "ids": ["The ids field is required."]
  }
}
```

### Examples
#### Example: Permanently delete selected trashed sub-brands
Request:
```json
{
  "ids": [1, 2]
}
```
Response:
```json
{
  "success": true,
  "message": "Bulk force deleted successfully."
}
```

### Notes
This endpoint requires `delete_sub_brand` and should be treated as irreversible.

## Branch: Deleted sub-brand should be recovered
**Condition:** A company user deleted a sub-brand by mistake.

### Case: Restore from trash
**When:** The sub-brand appears in `List Trashed Sub-Brands` and should return to the active catalog.
**Explanation:** Use `Restore Sub-Brand` for one ID or `Bulk Restore Sub-Brands` for multiple IDs, then reload `List Sub-Brands` to confirm it is back in the catalog.

#### Endpoint: Restore Sub-Brand
- **Method:** POST
- **URL:** /api/v1/company/sub-brands/trash/{id}/restore
