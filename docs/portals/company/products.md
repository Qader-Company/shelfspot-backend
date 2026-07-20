# Flow: Company Product Management

## Description
Company Product Management documents how company users list, create, view, update, delete, and bulk-delete products inside the current company tenant.

## Business Goal
Allow a company to maintain its own product catalog so task creation and reporting can reference clean company-scoped product data.

## Module Overview
This flow belongs to the company portal Products module. Endpoints are under `/api/v1/company/products`, require a company Bearer token, resolve tenant data through `X-Company-Slug`, and enforce product permissions per action.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- The acting company user has the required permission: `view_product`, `create_product`, `edit_product`, or `delete_product`.
- `brand_id`, `sub_brand_id`, `category_id`, and `sub_category_id` are optional, but when sent they must reference records in the current company.
- If `sub_brand_id` is sent, `brand_id` is required and the sub-brand must belong to that brand.
- If `sub_category_id` is sent, `category_id` is required and the sub-category must belong to that category.
- `sku` is optional but must be unique per current company when sent.
- Image uploads, when sent, must be image files with one of: `jpeg`, `png`, `jpg`, `gif`, `svg`, max `2048 KB`.

## Walkthrough
1. Call `List Products` to show paginated company products and optional filters.
2. Call `Filter Product Catalog Options` when the UI needs cascading brand/sub-brand/category/sub-category options.
3. Call `Create Product` with product name, required active flag, optional catalog relation IDs, optional SKU/description, and optional image.
4. Call `Show Product` to open one product by ID.
5. Call `Update Product` to edit product fields, image, relation IDs, SKU, description, or active state.
6. Call `Delete Product` for a single soft-delete, or `Bulk Delete Products` for multiple IDs.

## Endpoint: List Products
- **Method:** GET
- **URL:** /api/v1/company/products
- **Auth:** Bearer
- **Purpose:** Return paginated products scoped to the current company.

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
        "name": "Acme Cola 330ml",
        "image": "https://cdn.example.com/products/acme-cola-330ml.png",
        "active": true
      }
    ],
    "links": {
      "first": "http://localhost/api/v1/company/products?page=1",
      "last": "http://localhost/api/v1/company/products?page=1",
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
#### Example: List active products by name
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
      { "id": 1, "name": "Acme Cola 330ml", "image": "https://cdn.example.com/products/acme-cola-330ml.png", "active": true }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
The controller accepts `search`, `active`, `brand_id`, `sub_brand_id`, `category_id`, and `sub_category_id` filters from the query string. Results are tenant-scoped by the current company.

## Endpoint: Filter Product Catalog Options
- **Method:** GET
- **URL:** /api/v1/company/products/filter-options
- **Auth:** Bearer
- **Purpose:** Return cascading brand, sub-brand, category, and sub-category options for product filters/forms.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{
  "brand_id": "integer (required, must exist in current company brands)",
  "sub_brand_id": "integer (optional, must exist in current company sub-brands)",
  "category_id": "integer (optional, must exist in current company categories)",
  "sub_category_id": "integer (optional, must exist in current company sub-categories)"
}
```

### Success (200)
```json
{
  "success": true,
  "data": {
    "data": {
      "brands": [{ "id": 1, "label": "Acme" }],
      "sub_brands": [{ "id": 2, "label": "Acme Drinks" }],
      "categories": [{ "id": 3, "label": "Beverages" }],
      "sub_categories": [{ "id": 4, "label": "Soft Drinks" }]
    },
    "meta": {
      "applied_filters": {
        "brand_id": 1,
        "sub_brand_id": 2,
        "category_id": 3,
        "sub_category_id": null
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

- **422 — validation error**
```json
{
  "message": "The brand id field is required.",
  "errors": {
    "brand_id": ["The brand id field is required."]
  }
}
```

### Examples
#### Example: Resolve product filter options
Request:
```json
{
  "brand_id": 1,
  "sub_brand_id": 2,
  "category_id": 3
}
```
Response:
```json
{
  "success": true,
  "data": {
    "data": {
      "brands": [{ "id": 1, "label": "Acme" }],
      "sub_brands": [{ "id": 2, "label": "Acme Drinks" }],
      "categories": [{ "id": 3, "label": "Beverages" }],
      "sub_categories": [{ "id": 4, "label": "Soft Drinks" }]
    },
    "meta": { "applied_filters": { "brand_id": 1, "sub_brand_id": 2, "category_id": 3, "sub_category_id": null } }
  }
}
```

### Notes
`brand_id` is required for this endpoint. Optional IDs narrow dependent options and must exist in the current company.

## Endpoint: Create Product
- **Method:** POST
- **URL:** /api/v1/company/products
- **Auth:** Bearer
- **Purpose:** Create a new product for the current company.

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
  "sub_brand_id": "integer (optional, must exist in current company sub-brands; requires brand_id and must belong to brand_id)",
  "category_id": "integer (optional, must exist in current company categories)",
  "sub_category_id": "integer (optional, must exist in current company sub-categories; requires category_id and must belong to category_id)",
  "description": "string (optional)",
  "sku": "string (optional, max:255, unique per company)",
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
    "brand_id": ["The brand field is required when sub brand is selected."],
    "sub_brand_id": ["The selected sub brand does not belong to the selected brand."],
    "category_id": ["The category field is required when sub category is selected."],
    "sub_category_id": ["The selected sub category does not belong to the selected category."],
    "sku": ["The sku has already been taken."]
  }
}
```

### Examples
#### Example: Create active product
Request:
```json
{
  "name": "Acme Cola 330ml",
  "brand_id": 1,
  "sub_brand_id": 2,
  "category_id": 3,
  "sub_category_id": 4,
  "description": "330ml cola can",
  "sku": "ACME-COLA-330",
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
Product creation automatically attaches the current tenant company ID, validates optional catalog relation IDs against the current company, enforces sub-brand/sub-category parent consistency, enforces company-scoped SKU uniqueness, and stores the optional image in the single-file `image` media collection.

## Endpoint: Show Product
- **Method:** GET
- **URL:** /api/v1/company/products/{id}
- **Auth:** Bearer
- **Purpose:** Return one company product by ID.

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
    "name": "Acme Cola 330ml",
    "image": "https://cdn.example.com/products/acme-cola-330ml.png",
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

- **404 — product not found**
```json
{ "success": false, "message": "Product not found." }
```

### Examples
#### Example: Open product details
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
    "name": "Acme Cola 330ml",
    "image": "https://cdn.example.com/products/acme-cola-330ml.png",
    "active": true
  }
}
```

### Notes
The `{id}` must belong to the current company because products use company tenant scoping. Product responses can include `brand`, `sub_brand`, `category`, and `sub_category` relations when loaded.

## Endpoint: Update Product
- **Method:** PATCH
- **URL:** /api/v1/company/products/{id}
- **Auth:** Bearer
- **Purpose:** Update a product name, image, or active state.

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
  "sub_brand_id": "integer (optional nullable, must exist in current company sub-brands when present; requires/effectively uses brand_id)",
  "category_id": "integer (optional nullable, must exist in current company categories when present)",
  "sub_category_id": "integer (optional nullable, must exist in current company sub-categories when present; requires/effectively uses category_id)",
  "description": "string (optional nullable)",
  "sku": "string (optional nullable, max:255, unique per company except current product)",
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

- **404 — product not found**
```json
{ "success": false, "message": "Product not found." }
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
#### Example: Deactivate product
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

## Endpoint: Delete Product
- **Method:** DELETE
- **URL:** /api/v1/company/products/{id}
- **Auth:** Bearer
- **Purpose:** Soft-delete a product and queue cascading catalog delete behavior for its children.

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

- **404 — product not found**
```json
{ "success": false, "message": "Product not found." }
```

### Examples
#### Example: Delete one product
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
The delete endpoint uses the shared deleted response. Product trash actions only affect product records and associated product media.

## Endpoint: Bulk Delete Products
- **Method:** POST
- **URL:** /api/v1/company/products/bulk-delete
- **Auth:** Bearer
- **Purpose:** Soft-delete multiple products by ID.

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
  "message": "Bulk deleted successfully."
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
#### Example: Bulk delete selected products
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
  "message": "Bulk deleted successfully."
}
```

### Notes
This endpoint accepts up to 100 distinct IDs per request and requires `delete_product`.

## Branch: Product image replacement
**Condition:** A company updates a product and sends a new `image` file.

### Case: Replace single-file image collection
**When:** The product already has an image and the update request includes a new valid image file.
**Explanation:** The backend clears the old `image` media collection and stores the new file as the product image.

#### Endpoint: Update Product
- **Method:** PATCH
- **URL:** /api/v1/company/products/{id}

---
# Flow: Company Product Excel Operations

## Description
Company Product Excel Operations documents how company users download an import template, export current products, and import products from an Excel or CSV file.

## Business Goal
Allow companies to manage product catalog data in bulk instead of manually creating or updating each product one by one.

## Module Overview
This flow belongs to the company portal Products Excel support. Endpoints are under `/api/v1/company/products/excel` and reuse company auth, tenant scoping, and product permissions.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_product` for template/export and `create_product` for import.
- Import files must be `xlsx`, `xls`, or `csv`, max `10240 KB`.

## Walkthrough
1. Call `Download Product Template` to get the expected spreadsheet structure.
2. Fill product rows in the template outside the API.
3. Call `Import Products` with the completed spreadsheet.
4. Review row-level errors returned by the import response, if any.
5. Call `Export Products` whenever the company needs a spreadsheet snapshot of current product data.

## Endpoint: Download Product Template
- **Method:** GET
- **URL:** /api/v1/company/products/excel/template
- **Auth:** Bearer
- **Purpose:** Download an Excel template for product import.

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
#### Example: Download product import template
Request:
```json
{}
```
Response:
```json
{
  "file": "products-template.xlsx"
}
```

### Notes
This endpoint returns a binary file response rather than the normal JSON API format.

## Endpoint: Export Products
- **Method:** GET
- **URL:** /api/v1/company/products/excel/export
- **Auth:** Bearer
- **Purpose:** Download current company product data as a spreadsheet.

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
#### Example: Export product catalog
Request:
```json
{}
```
Response:
```json
{
  "file": "products-export.xlsx"
}
```

### Notes
This endpoint returns a binary file response and requires `view_product`.

## Endpoint: Import Products
- **Method:** POST
- **URL:** /api/v1/company/products/excel/import
- **Auth:** Bearer
- **Purpose:** Import product rows from an uploaded spreadsheet.

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
#### Example: Import product spreadsheet
Request:
```json
{
  "file": "products.xlsx"
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

#### Endpoint: Import Products
- **Method:** POST
- **URL:** /api/v1/company/products/excel/import

---
# Flow: Company Product Trash Management

## Description
Company Product Trash Management documents how company users list deleted products, restore deleted products, bulk-restore deleted products, force-delete one product, and bulk-force-delete deleted products.

## Business Goal
Allow companies to recover mistakenly deleted product catalog data or permanently purge deleted products when they are no longer needed.

## Module Overview
This flow belongs to the shared catalog trash behavior used by company products. Endpoints are under `/api/v1/company/products/trash` and operate only on trashed product records in the current company tenant.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `view_product` for trash list, `edit_product` for restore, and `delete_product` for force-delete.
- Bulk actions require an `ids` array with 1 to 100 distinct integer IDs.

## Walkthrough
1. Call `List Trashed Products` to show deleted products.
2. Call `Restore Product` to recover one deleted product.
3. Call `Bulk Restore Products` to recover multiple deleted products.
4. Call `Force Delete Product` to permanently delete one trashed product.
5. Call `Bulk Force Delete Products` to permanently delete multiple trashed products.

## Endpoint: List Trashed Products
- **Method:** GET
- **URL:** /api/v1/company/products/trash
- **Auth:** Bearer
- **Purpose:** Return paginated soft-deleted products for the current company.

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
        "name": "Acme Cola 330ml",
        "image": "https://cdn.example.com/products/acme-cola-330ml.png",
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
#### Example: List deleted products
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
      { "id": 1, "deleted_at": "2026-07-02T12:00:00.000000Z", "purge_status": "pending", "name": "Acme Cola 330ml", "image": "", "active": false }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1 }
  }
}
```

### Notes
This endpoint requires `view_product` and returns deleted records only.

## Endpoint: Restore Product
- **Method:** POST
- **URL:** /api/v1/company/products/trash/{id}/restore
- **Auth:** Bearer
- **Purpose:** Restore one soft-deleted product by ID.

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

- **404 — trashed product not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Restore one product
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
This endpoint requires `edit_product`.

## Endpoint: Bulk Restore Products
- **Method:** POST
- **URL:** /api/v1/company/products/trash/bulk-restore
- **Auth:** Bearer
- **Purpose:** Restore multiple soft-deleted products by ID.

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
#### Example: Restore selected products
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
This endpoint requires `edit_product`.

## Endpoint: Force Delete Product
- **Method:** DELETE
- **URL:** /api/v1/company/products/trash/{id}
- **Auth:** Bearer
- **Purpose:** Permanently delete one trashed product by ID.

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

- **404 — trashed product not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Permanently delete product
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
This endpoint requires `delete_product`. Force delete removes the product permanently and deletes associated media on force delete.

## Endpoint: Bulk Force Delete Products
- **Method:** DELETE
- **URL:** /api/v1/company/products/trash/bulk-force-delete
- **Auth:** Bearer
- **Purpose:** Permanently delete multiple trashed products by ID.

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
#### Example: Permanently delete selected trashed products
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
This endpoint requires `delete_product` and should be treated as irreversible.

## Branch: Deleted product should be recovered
**Condition:** A company user deleted a product by mistake.

### Case: Restore from trash
**When:** The product appears in `List Trashed Products` and should return to the active catalog.
**Explanation:** Use `Restore Product` for one ID or `Bulk Restore Products` for multiple IDs, then reload `List Products` to confirm it is back in the catalog.

#### Endpoint: Restore Product
- **Method:** POST
- **URL:** /api/v1/company/products/trash/{id}/restore
