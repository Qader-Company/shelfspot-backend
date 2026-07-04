# Flow: Company Sub-Category Management

## Description
Company Sub-Category Management documents how company users list, create, view, update, delete, and bulk-delete sub-categories inside the current company tenant.

## Business Goal
Allow a company to maintain its own sub-category catalog so products and tasks can reference clean company-scoped sub-category data under a required parent category.

## Module Overview
This flow belongs to the company portal Sub-Categories module. Endpoints are under `/api/v1/company/sub-categories`, require a company Bearer token, resolve tenant data through `X-Company-Slug`, and enforce sub-category permissions per action.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- The acting company user has the required permission: `view_sub_category`, `create_sub_category`, `edit_sub_category`, or `delete_sub_category`.
- `category_id` is required and must reference a category in the current company.
- `brand_id` and `sub_brand_id` are optional, but when sent they must reference records in the current company.
- Image uploads, when sent, must be image files with one of: `jpeg`, `png`, `jpg`, `gif`, `svg`, max `2048 KB`.

## Walkthrough
1. Call `List Sub-Categories` to show paginated company sub-categories and optional filters.
2. Call `Create Sub-Category` with sub-category name, required `category_id`, required active flag, optional `brand_id`, optional `sub_brand_id`, and optional image.
3. Call `Show Sub-Category` to open one sub-category by ID.
4. Call `Update Sub-Category` to edit name, image, or active state.
5. Call `Delete Sub-Category` for a single soft-delete, or `Bulk Delete Sub-Categories` for multiple IDs.

## Endpoint: List Sub-Categories
- **Method:** GET
- **URL:** /api/v1/company/sub-categories
- **Auth:** Bearer
- **Purpose:** Return paginated sub-categories scoped to the current company.

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
        "name": "Soft Drinks",
        "image": "https://cdn.example.com/sub-categories/soft-drinks.png",
        "active": true
      }
    ],
    "links": {
      "first": "http://localhost/api/v1/company/sub-categories?page=1",
      "last": "http://localhost/api/v1/company/sub-categories?page=1",
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
#### Example: List active sub-categories by name
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
      { "id": 1, "name": "Soft Drinks", "image": "https://cdn.example.com/sub-categories/soft-drinks.png", "active": true }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
The controller accepts `name`, `active`, `brand_id`, `sub_brand_id`, and `category_id` filters from the query string. Results are tenant-scoped by the current company.

## Endpoint: Create Sub-Category
- **Method:** POST
- **URL:** /api/v1/company/sub-categories
- **Auth:** Bearer
- **Purpose:** Create a new sub-category for the current company.

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
  "brand_id": "integer (optional, must exist in current company brands)",
  "sub_brand_id": "integer (optional, must exist in current company sub-brands)",
  "category_id": "integer (required, must exist in current company categories)",
  "is_active": "boolean (required)",
  "image": "file (optional, image, mimes:jpeg,png,jpg,gif,svg, max:2048KB)"
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
    "is_active": ["The is active field is required."],
    "category_id": ["The selected category is invalid."],
    "brand_id": ["The selected brand is invalid."],
    "sub_brand_id": ["The selected sub brand is invalid."]
  }
}
```

### Examples
#### Example: Create active sub-category
Request:
```json
{
  "name": "Soft Drinks",
  "brand_id": 1,
  "sub_brand_id": 2,
  "category_id": 3,
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
Sub-category creation automatically attaches the current tenant company ID, validates required `category_id` and optional `brand_id`/`sub_brand_id` against the current company, and stores the optional image in the single-file `image` media collection.

## Endpoint: Show Sub-Category
- **Method:** GET
- **URL:** /api/v1/company/sub-categories/{id}
- **Auth:** Bearer
- **Purpose:** Return one company sub-category by ID.

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
    "name": "Soft Drinks",
    "image": "https://cdn.example.com/sub-categories/soft-drinks.png",
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

- **404 — sub-category not found**
```json
{ "success": false, "message": "Sub-sub-category not found." }
```

### Examples
#### Example: Open sub-category details
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
    "name": "Soft Drinks",
    "image": "https://cdn.example.com/sub-categories/soft-drinks.png",
    "active": true
  }
}
```

### Notes
The `{id}` must belong to the current company because sub-categories use company tenant scoping. The resource can include optional `brand`, `sub_brand`, and `category` relations when loaded.

## Endpoint: Update Sub-Category
- **Method:** PATCH
- **URL:** /api/v1/company/sub-categories/{id}
- **Auth:** Bearer
- **Purpose:** Update a sub-category name, image, or active state.

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
  "brand_id": "integer (optional nullable, must exist in current company brands when present)",
  "sub_brand_id": "integer (optional nullable, must exist in current company sub-brands when present)",
  "category_id": "integer (optional, must exist in current company categories when present)",
  "is_active": "boolean (optional)",
  "image": "file (optional nullable, image, mimes:jpeg,png,jpg,gif,svg, max:2048KB)"
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

- **404 — sub-category not found**
```json
{ "success": false, "message": "Sub-sub-category not found." }
```

- **422 — validation error**
```json
{
  "message": "The image field must be a file of type: jpeg, png, jpg, gif, svg.",
  "errors": {
    "image": ["The image field must be a file of type: jpeg, png, jpg, gif, svg."]
  }
}
```

### Examples
#### Example: Deactivate sub-category
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
The route supports both `PUT` and `PATCH`; prefer `PATCH` for partial updates. Sending a new image replaces the current image file.

## Endpoint: Delete Sub-Category
- **Method:** DELETE
- **URL:** /api/v1/company/sub-categories/{id}
- **Auth:** Bearer
- **Purpose:** Soft-delete a sub-category and queue cascading catalog delete behavior for its children.

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

- **404 — sub-category not found**
```json
{ "success": false, "message": "Sub-sub-category not found." }
```

### Examples
#### Example: Delete one sub-category
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
The delete endpoint returns an accepted queued-delete message in the controller. Related products are handled through cascade trash actions.

## Endpoint: Bulk Delete Sub-Categories
- **Method:** POST
- **URL:** /api/v1/company/sub-categories/bulk-delete
- **Auth:** Bearer
- **Purpose:** Soft-delete multiple sub-categories by ID.

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
#### Example: Bulk delete selected sub-categories
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
This endpoint accepts up to 100 distinct IDs per request and requires `delete_sub_category`.

## Branch: Sub-Category image replacement
**Condition:** A company updates a sub-category and sends a new `image` file.

### Case: Replace single-file image collection
**When:** The sub-category already has an image and the update request includes a new valid image file.
**Explanation:** The backend clears the old `image` media collection and stores the new file as the sub-category image.

#### Endpoint: Update Sub-Category
- **Method:** PATCH
- **URL:** /api/v1/company/sub-categories/{id}

---
# Flow: Company Sub-Category Excel Operations

## Description
Company Sub-Category Excel Operations documents how company users download an import template, export current sub-categories, and import sub-categories from an Excel or CSV file.

## Business Goal
Allow companies to manage sub-category catalog data in bulk instead of manually creating or updating each sub-category one by one.

## Module Overview
This flow belongs to the company portal Sub-Categories Excel support. Endpoints are under `/api/v1/company/sub-categories/excel` and reuse company auth, tenant scoping, and sub-category permissions.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_sub_category` for template/export and `create_sub_category` for import.
- Import files must be `xlsx`, `xls`, or `csv`, max `10240 KB`.

## Walkthrough
1. Call `Download Sub-Category Template` to get the expected spreadsheet structure.
2. Fill sub-category rows in the template outside the API.
3. Call `Import Sub-Categories` with the completed spreadsheet.
4. Review row-level errors returned by the import response, if any.
5. Call `Export Sub-Categories` whenever the company needs a spreadsheet snapshot of current sub-category data.

## Endpoint: Download Sub-Category Template
- **Method:** GET
- **URL:** /api/v1/company/sub-categories/excel/template
- **Auth:** Bearer
- **Purpose:** Download an Excel template for sub-category import.

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
#### Example: Download sub-category import template
Request:
```json
{}
```
Response:
```json
{
  "file": "sub-categories-template.xlsx"
}
```

### Notes
This endpoint returns a binary file response rather than the normal JSON API format.

## Endpoint: Export Sub-Categories
- **Method:** GET
- **URL:** /api/v1/company/sub-categories/excel/export
- **Auth:** Bearer
- **Purpose:** Download current company sub-category data as a spreadsheet.

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
#### Example: Export sub-category catalog
Request:
```json
{}
```
Response:
```json
{
  "file": "sub-categories-export.xlsx"
}
```

### Notes
This endpoint returns a binary file response and requires `view_sub_category`.

## Endpoint: Import Sub-Categories
- **Method:** POST
- **URL:** /api/v1/company/sub-categories/excel/import
- **Auth:** Bearer
- **Purpose:** Import sub-category rows from an uploaded spreadsheet.

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
#### Example: Import sub-category spreadsheet
Request:
```json
{
  "file": "sub-categories.xlsx"
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

#### Endpoint: Import Sub-Categories
- **Method:** POST
- **URL:** /api/v1/company/sub-categories/excel/import

---
# Flow: Company Sub-Category Trash Management

## Description
Company Sub-Category Trash Management documents how company users list deleted sub-categories, restore deleted sub-categories, bulk-restore deleted sub-categories, force-delete one sub-category, and bulk-force-delete deleted sub-categories.

## Business Goal
Allow companies to recover mistakenly deleted sub-category catalog data or permanently purge deleted sub-categories when they are no longer needed.

## Module Overview
This flow belongs to the shared catalog trash behavior used by company sub-categories. Endpoints are under `/api/v1/company/sub-categories/trash` and operate only on trashed sub-category records in the current company tenant.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_sub_category` for trash list, `edit_sub_category` for restore, and `delete_sub_category` for force-delete.
- Bulk actions require an `ids` array with 1 to 100 distinct integer IDs.

## Walkthrough
1. Call `List Trashed Sub-Categories` to show deleted sub-categories.
2. Call `Restore Sub-Category` to recover one deleted sub-category.
3. Call `Bulk Restore Sub-Categories` to recover multiple deleted sub-categories.
4. Call `Force Delete Sub-Category` to permanently delete one trashed sub-category.
5. Call `Bulk Force Delete Sub-Categories` to permanently delete multiple trashed sub-categories.

## Endpoint: List Trashed Sub-Categories
- **Method:** GET
- **URL:** /api/v1/company/sub-categories/trash
- **Auth:** Bearer
- **Purpose:** Return paginated soft-deleted sub-categories for the current company.

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
        "name": "Soft Drinks",
        "image": "https://cdn.example.com/sub-categories/soft-drinks.png",
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
#### Example: List deleted sub-categories
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
      { "id": 1, "deleted_at": "2026-07-02T12:00:00.000000Z", "purge_status": "pending", "name": "Soft Drinks", "image": "", "active": false }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
This endpoint requires `view_sub_category` and returns deleted records only.

## Endpoint: Restore Sub-Category
- **Method:** POST
- **URL:** /api/v1/company/sub-categories/trash/{id}/restore
- **Auth:** Bearer
- **Purpose:** Restore one soft-deleted sub-category by ID.

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

- **404 — trashed sub-category not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Restore one sub-category
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
This endpoint requires `edit_sub_category`.

## Endpoint: Bulk Restore Sub-Categories
- **Method:** POST
- **URL:** /api/v1/company/sub-categories/trash/bulk-restore
- **Auth:** Bearer
- **Purpose:** Restore multiple soft-deleted sub-categories by ID.

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
#### Example: Restore selected sub-categories
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
This endpoint requires `edit_sub_category`.

## Endpoint: Force Delete Sub-Category
- **Method:** DELETE
- **URL:** /api/v1/company/sub-categories/trash/{id}
- **Auth:** Bearer
- **Purpose:** Permanently delete one trashed sub-category by ID.

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

- **404 — trashed sub-category not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Permanently delete sub-category
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
This endpoint requires `delete_sub_category`. Force delete removes the sub-category permanently and deletes associated media on force delete.

## Endpoint: Bulk Force Delete Sub-Categories
- **Method:** DELETE
- **URL:** /api/v1/company/sub-categories/trash/bulk-force-delete
- **Auth:** Bearer
- **Purpose:** Permanently delete multiple trashed sub-categories by ID.

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
#### Example: Permanently delete selected trashed sub-categories
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
This endpoint requires `delete_sub_category` and should be treated as irreversible.

## Branch: Deleted sub-category should be recovered
**Condition:** A company user deleted a sub-category by mistake.

### Case: Restore from trash
**When:** The sub-category appears in `List Trashed Sub-Categories` and should return to the active catalog.
**Explanation:** Use `Restore Sub-Category` for one ID or `Bulk Restore Sub-Categories` for multiple IDs, then reload `List Sub-Categories` to confirm it is back in the catalog.

#### Endpoint: Restore Sub-Category
- **Method:** POST
- **URL:** /api/v1/company/sub-categories/trash/{id}/restore
