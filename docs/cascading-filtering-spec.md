# Cascading Filtering Specification (Brand, Sub Brand, Category, Sub Category)

## Goal
Provide a backend-driven filtering contract for frontend select boxes where:
- `brand_id` is always required.
- `sub_brand_id`, `category_id`, and `sub_category_id` are optional.
- Category options depend on selected brand and optional sub brand.
- Sub category options depend on selected brand, optional sub brand, and optional category.

## API Endpoint
`GET /api/v1/company/products/filter-options`

### Query Parameters
- `brand_id` (required, integer)
- `sub_brand_id` (optional, integer)
- `category_id` (optional, integer)
- `sub_category_id` (optional, integer)

## Validation Rules
- `brand_id` is required and must exist in `brands.id`.
- optional IDs must exist in their tables.
- effective scoping is resolved server-side by applying parent constraints in queries.

## Response Contract
```json
{
  "data": {
    "brands": [{"id": 1, "value": 1, "label": "Brand A"}],
    "sub_brands": [{"id": 3, "value": 3, "label": "Sub Brand X"}],
    "categories": [{"id": 6, "value": 6, "label": "Category 1"}],
    "sub_categories": [{"id": 11, "value": 11, "label": "Sub Category I"}]
  },
  "meta": {
    "applied_filters": {
      "brand_id": 1,
      "sub_brand_id": 3,
      "category_id": 6,
      "sub_category_id": null
    }
  }
}
```

## Backend Filtering Semantics
1. Sub brands are loaded by `brand_id`.
2. Categories are loaded by `brand_id` and optional `sub_brand_id`.
3. Sub categories are loaded by `brand_id`, optional `sub_brand_id`, and optional `category_id`.
4. Only active records are returned.

## Frontend Synchronization Rules
- On brand change, clear sub brand/category/sub category selections.
- On sub brand change, clear category/sub category selections.
- On category change, clear sub category selection.
- Re-fetch options after each parent selection change.

## Architecture Notes
- Keep controller thin; delegate option assembly to an application service.
- Use dedicated request validation for endpoint input contract.
- Return uniform `id/value/label` option DTOs for all select lists.
