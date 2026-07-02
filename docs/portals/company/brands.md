# Flow: Company Brand Management

## Description
Company Brand Management documents how company users list, create, view, update, delete, and bulk-delete brands inside the current company tenant.

## Business Goal
Allow a company to maintain its own brand catalog so products, categories, sub-brands, and tasks can reference clean company-scoped catalog data.

## Module Overview
This flow belongs to the company portal Brands module. Endpoints are under `/api/v1/company/brands`, require a company Bearer token, resolve tenant data through `X-Company-Slug`, and enforce brand permissions per action.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- The acting company user has the required permission: `view_brand`, `create_brand`, `edit_brand`, or `delete_brand`.
- Logo uploads, when sent, must be image files with one of: `jpeg`, `png`, `jpg`, `gif`, `svg`, max `2048 KB`.

## Walkthrough
1. Call `List Brands` to show paginated company brands and optional filters.
2. Call `Create Brand` with a brand name, optional logo, and optional active flag.
3. Call `Show Brand` to open one brand by ID.
4. Call `Update Brand` to edit name, logo, or active state.
5. Call `Delete Brand` for a single soft-delete, or `Bulk Delete Brands` for multiple IDs.

## Endpoint: List Brands
- **Method:** GET
- **URL:** /api/v1/company/brands
- **Auth:** Bearer
- **Purpose:** Return paginated brands scoped to the current company.

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
        "name": "Acme",
        "logo": "https://cdn.example.com/brands/acme.png",
        "active": true
      }
    ],
    "links": {
      "first": "http://localhost/api/v1/company/brands?page=1",
      "last": "http://localhost/api/v1/company/brands?page=1",
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
#### Example: List active brands by name
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
      { "id": 1, "name": "Acme", "logo": "https://cdn.example.com/brands/acme.png", "active": true }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
The controller accepts `name` and `active` filters from the query string. Results are tenant-scoped by the current company.

## Endpoint: Create Brand
- **Method:** POST
- **URL:** /api/v1/company/brands
- **Auth:** Bearer
- **Purpose:** Create a new brand for the current company.

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
  "name": "string (required, max:255)",
  "logo": "file (optional, image, mimes:jpeg,png,jpg,gif,svg, max:2048KB)",
  "is_active": "boolean (optional)"
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
    "name": ["The name field is required."],
    "logo": ["The logo field must be an image."]
  }
}
```

### Examples
#### Example: Create active brand
Request:
```json
{
  "name": "Acme",
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
Brand creation automatically attaches the current tenant company ID. When `logo` is provided, it is stored in the single-file `logo` media collection.

## Endpoint: Show Brand
- **Method:** GET
- **URL:** /api/v1/company/brands/{id}
- **Auth:** Bearer
- **Purpose:** Return one company brand by ID.

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
    "name": "Acme",
    "logo": "https://cdn.example.com/brands/acme.png",
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

- **404 — brand not found**
```json
{ "success": false, "message": "Brand not found." }
```

### Examples
#### Example: Open brand details
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
    "name": "Acme",
    "logo": "https://cdn.example.com/brands/acme.png",
    "active": true
  }
}
```

### Notes
The `{id}` must belong to the current company because brands use company tenant scoping.

## Endpoint: Update Brand
- **Method:** PATCH
- **URL:** /api/v1/company/brands/{id}
- **Auth:** Bearer
- **Purpose:** Update a brand name, logo, or active state.

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
  "name": "string (optional, max:255)",
  "logo": "file (optional, image, mimes:jpeg,png,jpg,gif,svg, max:2048KB)",
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

- **404 — brand not found**
```json
{ "success": false, "message": "Brand not found." }
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
#### Example: Deactivate brand
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
The route supports both `PUT` and `PATCH`; prefer `PATCH` for partial updates. Sending a new logo replaces the current logo file.

## Endpoint: Delete Brand
- **Method:** DELETE
- **URL:** /api/v1/company/brands/{id}
- **Auth:** Bearer
- **Purpose:** Soft-delete a brand and queue cascading catalog delete behavior for its children.

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

- **404 — brand not found**
```json
{ "success": false, "message": "Brand not found." }
```

### Examples
#### Example: Delete one brand
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
The delete endpoint returns an accepted queued-delete message in the controller. Related catalog children are handled through cascade trash actions.

## Endpoint: Bulk Delete Brands
- **Method:** POST
- **URL:** /api/v1/company/brands/bulk-delete
- **Auth:** Bearer
- **Purpose:** Soft-delete multiple brands by ID.

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
#### Example: Bulk delete selected brands
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
This endpoint accepts up to 100 distinct IDs per request and requires `delete_brand`.

## Branch: Brand logo replacement
**Condition:** A company updates a brand and sends a new `logo` file.

### Case: Replace single-file logo collection
**When:** The brand already has a logo and the update request includes a new valid image file.
**Explanation:** The backend clears the old `logo` media collection and stores the new file as the brand logo.

#### Endpoint: Update Brand
- **Method:** PATCH
- **URL:** /api/v1/company/brands/{id}

---
# Flow: Company Brand Excel Operations

## Description
Company Brand Excel Operations documents how company users download an import template, export current brands, and import brands from an Excel or CSV file.

## Business Goal
Allow companies to manage brand catalog data in bulk instead of manually creating or updating each brand one by one.

## Module Overview
This flow belongs to the company portal Brands Excel support. Endpoints are under `/api/v1/company/brands/excel` and reuse company auth, tenant scoping, and brand permissions.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_brand` for template/export and `create_brand` for import.
- Import files must be `xlsx`, `xls`, or `csv`, max `10240 KB`.

## Walkthrough
1. Call `Download Brand Template` to get the expected spreadsheet structure.
2. Fill brand rows in the template outside the API.
3. Call `Import Brands` with the completed spreadsheet.
4. Review row-level errors returned by the import response, if any.
5. Call `Export Brands` whenever the company needs a spreadsheet snapshot of current brand data.

## Endpoint: Download Brand Template
- **Method:** GET
- **URL:** /api/v1/company/brands/excel/template
- **Auth:** Bearer
- **Purpose:** Download an Excel template for brand import.

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
#### Example: Download brand import template
Request:
```json
{}
```
Response:
```json
{
  "file": "brands-template.xlsx"
}
```

### Notes
This endpoint returns a binary file response rather than the normal JSON API format.

## Endpoint: Export Brands
- **Method:** GET
- **URL:** /api/v1/company/brands/excel/export
- **Auth:** Bearer
- **Purpose:** Download current company brand data as a spreadsheet.

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
#### Example: Export brand catalog
Request:
```json
{}
```
Response:
```json
{
  "file": "brands-export.xlsx"
}
```

### Notes
This endpoint returns a binary file response and requires `view_brand`.

## Endpoint: Import Brands
- **Method:** POST
- **URL:** /api/v1/company/brands/excel/import
- **Auth:** Bearer
- **Purpose:** Import brand rows from an uploaded spreadsheet.

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
#### Example: Import brand spreadsheet
Request:
```json
{
  "file": "brands.xlsx"
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

#### Endpoint: Import Brands
- **Method:** POST
- **URL:** /api/v1/company/brands/excel/import

---
# Flow: Company Brand Trash Management

## Description
Company Brand Trash Management documents how company users list deleted brands, restore deleted brands, bulk-restore deleted brands, force-delete one brand, and bulk-force-delete deleted brands.

## Business Goal
Allow companies to recover mistakenly deleted brand catalog data or permanently purge deleted brands when they are no longer needed.

## Module Overview
This flow belongs to the shared catalog trash behavior used by company brands. Endpoints are under `/api/v1/company/brands/trash` and operate only on trashed brand records in the current company tenant.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_brand` for trash list, `edit_brand` for restore, and `delete_brand` for force-delete.
- Bulk actions require an `ids` array with 1 to 100 distinct integer IDs.

## Walkthrough
1. Call `List Trashed Brands` to show deleted brands.
2. Call `Restore Brand` to recover one deleted brand.
3. Call `Bulk Restore Brands` to recover multiple deleted brands.
4. Call `Force Delete Brand` to permanently delete one trashed brand.
5. Call `Bulk Force Delete Brands` to permanently delete multiple trashed brands.

## Endpoint: List Trashed Brands
- **Method:** GET
- **URL:** /api/v1/company/brands/trash
- **Auth:** Bearer
- **Purpose:** Return paginated soft-deleted brands for the current company.

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
        "name": "Acme",
        "logo": "https://cdn.example.com/brands/acme.png",
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
#### Example: List deleted brands
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
      { "id": 1, "deleted_at": "2026-07-02T12:00:00.000000Z", "purge_status": "pending", "name": "Acme", "logo": "", "active": false }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
This endpoint requires `view_brand` and returns deleted records only.

## Endpoint: Restore Brand
- **Method:** POST
- **URL:** /api/v1/company/brands/trash/{id}/restore
- **Auth:** Bearer
- **Purpose:** Restore one soft-deleted brand by ID.

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

- **404 — trashed brand not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Restore one brand
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
This endpoint requires `edit_brand`.

## Endpoint: Bulk Restore Brands
- **Method:** POST
- **URL:** /api/v1/company/brands/trash/bulk-restore
- **Auth:** Bearer
- **Purpose:** Restore multiple soft-deleted brands by ID.

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
#### Example: Restore selected brands
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
This endpoint requires `edit_brand`.

## Endpoint: Force Delete Brand
- **Method:** DELETE
- **URL:** /api/v1/company/brands/trash/{id}
- **Auth:** Bearer
- **Purpose:** Permanently delete one trashed brand by ID.

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

- **404 — trashed brand not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Permanently delete brand
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
This endpoint requires `delete_brand`. Force delete removes the brand permanently and deletes associated media on force delete.

## Endpoint: Bulk Force Delete Brands
- **Method:** DELETE
- **URL:** /api/v1/company/brands/trash/bulk-force-delete
- **Auth:** Bearer
- **Purpose:** Permanently delete multiple trashed brands by ID.

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
#### Example: Permanently delete selected trashed brands
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
This endpoint requires `delete_brand` and should be treated as irreversible.

## Branch: Deleted brand should be recovered
**Condition:** A company user deleted a brand by mistake.

### Case: Restore from trash
**When:** The brand appears in `List Trashed Brands` and should return to the active catalog.
**Explanation:** Use `Restore Brand` for one ID or `Bulk Restore Brands` for multiple IDs, then reload `List Brands` to confirm it is back in the catalog.

#### Endpoint: Restore Brand
- **Method:** POST
- **URL:** /api/v1/company/brands/trash/{id}/restore
