# Flow: Company Category Management

## Description
Company Category Management documents how company users list, create, view, update, delete, and bulk-delete categories inside the current company tenant.

## Business Goal
Allow a company to maintain its own category catalog so products, sub-categories, and tasks can reference clean company-scoped category data.

## Module Overview
This flow belongs to the company portal Categories module. Endpoints are under `/api/v1/company/categories`, require a company Bearer token, resolve tenant data through `X-Company-Slug`, and enforce category permissions per action.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- The acting company user has the required permission: `view_category`, `create_category`, `edit_category`, or `delete_category`.
- `brand_id` and `sub_brand_id` are optional, but when sent they must reference records in the current company.
- Image uploads, when sent, must be image files with one of: `jpeg`, `png`, `jpg`, `gif`, `svg`, max `2048 KB`.

## Walkthrough
1. Call `List Categories` to show paginated company categories and optional filters.
2. Call `Create Category` with category name, required active flag, optional `brand_id`, optional `sub_brand_id`, and optional image.
3. Call `Show Category` to open one category by ID.
4. Call `Update Category` to edit name, image, or active state.
5. Call `Delete Category` for a single soft-delete, or `Bulk Delete Categories` for multiple IDs.

## Endpoint: List Categories
- **Method:** GET
- **URL:** /api/v1/company/categories
- **Auth:** Bearer
- **Purpose:** Return paginated categories scoped to the current company.

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
        "name": "Beverages",
        "image": "https://cdn.example.com/categories/beverages.png",
        "active": true
      }
    ],
    "links": {
      "first": "http://localhost/api/v1/company/categories?page=1",
      "last": "http://localhost/api/v1/company/categories?page=1",
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
#### Example: List active categories by name
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
      { "id": 1, "name": "Beverages", "image": "https://cdn.example.com/categories/beverages.png", "active": true }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
The controller accepts `name`, `active`, `brand_id`, and `sub_brand_id` filters from the query string. Results are tenant-scoped by the current company.

## Endpoint: Create Category
- **Method:** POST
- **URL:** /api/v1/company/categories
- **Auth:** Bearer
- **Purpose:** Create a new category for the current company.

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
    "brand_id": ["The selected brand is invalid."],
    "sub_brand_id": ["The selected sub brand is invalid."]
  }
}
```

### Examples
#### Example: Create active category
Request:
```json
{
  "name": "Beverages",
  "brand_id": 1,
  "sub_brand_id": 2,
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
Category creation automatically attaches the current tenant company ID, validates optional `brand_id` and `sub_brand_id` against the current company, and stores the optional image in the single-file `image` media collection.

## Endpoint: Show Category
- **Method:** GET
- **URL:** /api/v1/company/categories/{id}
- **Auth:** Bearer
- **Purpose:** Return one company category by ID.

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
    "name": "Beverages",
    "image": "https://cdn.example.com/categories/beverages.png",
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

- **404 — category not found**
```json
{ "success": false, "message": "Category not found." }
```

### Examples
#### Example: Open category details
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
    "name": "Beverages",
    "image": "https://cdn.example.com/categories/beverages.png",
    "active": true
  }
}
```

### Notes
The `{id}` must belong to the current company because categories use company tenant scoping. The index response loads optional `brand` and `sub_brand` relations.

## Endpoint: Update Category
- **Method:** PATCH
- **URL:** /api/v1/company/categories/{id}
- **Auth:** Bearer
- **Purpose:** Update a category name, image, or active state.

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
  "is_active": "boolean (optional)",
  "image": "file (optional nullable, image, mimes:jpeg,png,jpg,gif,svg, max:2048KB)",
  "image_action": "keep | remove | replace (optional)"
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

- **404 — category not found**
```json
{ "success": false, "message": "Category not found." }
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
#### Example: Deactivate category
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
The route supports both `PUT` and `PATCH`; prefer `PATCH` for partial updates. Omit both image fields to keep the current image. Use `image_action=remove` to delete it, or `image_action=replace` with a new `image` file to replace it. A new image without an action remains supported and replaces the current image.

## Endpoint: Delete Category
- **Method:** DELETE
- **URL:** /api/v1/company/categories/{id}
- **Auth:** Bearer
- **Purpose:** Soft-delete a category and queue cascading catalog delete behavior for its children.

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

- **404 — category not found**
```json
{ "success": false, "message": "Category not found." }
```

### Examples
#### Example: Delete one category
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
The delete endpoint returns an accepted queued-delete message in the controller. Related sub-categories and products are handled through cascade trash actions.

## Endpoint: Bulk Delete Categories
- **Method:** POST
- **URL:** /api/v1/company/categories/bulk-delete
- **Auth:** Bearer
- **Purpose:** Soft-delete multiple categories by ID.

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
#### Example: Bulk delete selected categories
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
This endpoint accepts up to 100 distinct IDs per request and requires `delete_category`.

## Branch: Category image replacement
**Condition:** A company updates a category and sends a new `image` file.

### Case: Replace single-file image collection
**When:** The category already has an image and the update request includes a new valid image file.
**Explanation:** The backend clears the old `image` media collection and stores the new file as the category image.

#### Endpoint: Update Category
- **Method:** PATCH
- **URL:** /api/v1/company/categories/{id}

---
# Flow: Company Category Excel Operations

## Description
Company Category Excel Operations documents how company users download an import template, export current categories, and import categories from an Excel or CSV file.

## Business Goal
Allow companies to manage category catalog data in bulk instead of manually creating or updating each category one by one.

## Module Overview
This flow belongs to the company portal Categories Excel support. Endpoints are under `/api/v1/company/categories/excel` and reuse company auth, tenant scoping, and category permissions.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_category` for template/export and `create_category` for import.
- Import files must be `xlsx`, `xls`, or `csv`, max `10240 KB`.

## Walkthrough
1. Call `Download Category Template` to get the expected spreadsheet structure.
2. Fill category rows in the template outside the API.
3. Call `Import Categories` with the completed spreadsheet.
4. Review row-level errors returned by the import response, if any.
5. Call `Export Categories` whenever the company needs a spreadsheet snapshot of current category data.

## Endpoint: Download Category Template
- **Method:** GET
- **URL:** /api/v1/company/categories/excel/template
- **Auth:** Bearer
- **Purpose:** Download an Excel template for category import.

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
#### Example: Download category import template
Request:
```json
{}
```
Response:
```json
{
  "file": "categories-template.xlsx"
}
```

### Notes
This endpoint returns a binary file response rather than the normal JSON API format.

## Endpoint: Export Categories
- **Method:** GET
- **URL:** /api/v1/company/categories/excel/export
- **Auth:** Bearer
- **Purpose:** Download current company category data as a spreadsheet.

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
#### Example: Export category catalog
Request:
```json
{}
```
Response:
```json
{
  "file": "categories-export.xlsx"
}
```

### Notes
This endpoint returns a binary file response and requires `view_category`.

## Endpoint: Import Categories
- **Method:** POST
- **URL:** /api/v1/company/categories/excel/import
- **Auth:** Bearer
- **Purpose:** Import category rows from an uploaded spreadsheet.

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
#### Example: Import category spreadsheet
Request:
```json
{
  "file": "categories.xlsx"
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

#### Endpoint: Import Categories
- **Method:** POST
- **URL:** /api/v1/company/categories/excel/import

---
# Flow: Company Category Trash Management

## Description
Company Category Trash Management documents how company users list deleted categories, restore deleted categories, bulk-restore deleted categories, force-delete one category, and bulk-force-delete deleted categories.

## Business Goal
Allow companies to recover mistakenly deleted category catalog data or permanently purge deleted categories when they are no longer needed.

## Module Overview
This flow belongs to the shared catalog trash behavior used by company categories. Endpoints are under `/api/v1/company/categories/trash` and operate only on trashed category records in the current company tenant.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_category` for trash list, `edit_category` for restore, and `delete_category` for force-delete.
- Bulk actions require an `ids` array with 1 to 100 distinct integer IDs.

## Walkthrough
1. Call `List Trashed Categories` to show deleted categories.
2. Call `Restore Category` to recover one deleted category.
3. Call `Bulk Restore Categories` to recover multiple deleted categories.
4. Call `Force Delete Category` to permanently delete one trashed category.
5. Call `Bulk Force Delete Categories` to permanently delete multiple trashed categories.

## Endpoint: List Trashed Categories
- **Method:** GET
- **URL:** /api/v1/company/categories/trash
- **Auth:** Bearer
- **Purpose:** Return paginated soft-deleted categories for the current company.

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
        "name": "Beverages",
        "image": "https://cdn.example.com/categories/beverages.png",
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
#### Example: List deleted categories
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
      { "id": 1, "deleted_at": "2026-07-02T12:00:00.000000Z", "purge_status": "pending", "name": "Beverages", "image": "", "active": false }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
This endpoint requires `view_category` and returns deleted records only.

## Endpoint: Restore Category
- **Method:** POST
- **URL:** /api/v1/company/categories/trash/{id}/restore
- **Auth:** Bearer
- **Purpose:** Restore one soft-deleted category by ID.

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

- **404 — trashed category not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Restore one category
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
This endpoint requires `edit_category`.

## Endpoint: Bulk Restore Categories
- **Method:** POST
- **URL:** /api/v1/company/categories/trash/bulk-restore
- **Auth:** Bearer
- **Purpose:** Restore multiple soft-deleted categories by ID.

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
#### Example: Restore selected categories
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
This endpoint requires `edit_category`.

## Endpoint: Force Delete Category
- **Method:** DELETE
- **URL:** /api/v1/company/categories/trash/{id}
- **Auth:** Bearer
- **Purpose:** Permanently delete one trashed category by ID.

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

- **404 — trashed category not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Permanently delete category
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
This endpoint requires `delete_category`. Force delete removes the category permanently and deletes associated media on force delete.

## Endpoint: Bulk Force Delete Categories
- **Method:** DELETE
- **URL:** /api/v1/company/categories/trash/bulk-force-delete
- **Auth:** Bearer
- **Purpose:** Permanently delete multiple trashed categories by ID.

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
#### Example: Permanently delete selected trashed categories
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
This endpoint requires `delete_category` and should be treated as irreversible.

## Branch: Deleted category should be recovered
**Condition:** A company user deleted a category by mistake.

### Case: Restore from trash
**When:** The category appears in `List Trashed Categories` and should return to the active catalog.
**Explanation:** Use `Restore Category` for one ID or `Bulk Restore Categories` for multiple IDs, then reload `List Categories` to confirm it is back in the catalog.

#### Endpoint: Restore Category
- **Method:** POST
- **URL:** /api/v1/company/categories/trash/{id}/restore
