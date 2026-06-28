# Flow Review: Catalog Family - Products

## Scope

### Portals

- Company portal product catalog routes under `/api/v1/company/products`.
- Admin-managed company product catalog routes under `/api/v1/admin/companies/{company}/products`.

### Main files

- `routes/V1/company/products.php`
- `routes/V1/admin/catalog-products.php`
- `app/Modules/V1/Products/Presentation/Http/Controller/ProductController.php`
- `app/Modules/V1/Products/Presentation/Http/Requests/StoreProductRequest.php`
- `app/Modules/V1/Products/Presentation/Http/Requests/UpdateProductRequest.php`
- `app/Modules/V1/Products/Presentation/Http/Resources/ProductResource.php`
- `app/Modules/V1/Products/Infrastructure/Persistence/Repositories/EloquentProductRepository.php`
- `app/Modules/V1/Products/Application/Services/ProductExcelService.php`
- `app/Modules/V1/Products/Application/Services/ProductFilterOptionsService.php`
- `app/Modules/Shared/Support/Traits/BelongsToCompany.php`
- `app/Modules/Shared/Support/Rules/ExistsInCurrentCompany.php`

## Current implementation summary

### 1. Tenant isolation

- `Product` uses `BelongsToCompany`, which applies the company global scope and automatically fills `company_id` from `TenantContext` on create.
- Company portal routes get tenant context from `X-Company-Slug`.
- Admin-managed product routes get tenant context from `{company}` through `tenant.route-company`.
- Relationship validation uses `ExistsInCurrentCompany` to ensure selected brand/sub-brand/category/sub-category exists in the current tenant company.

### 2. Product CRUD

- Index loads media and catalog relations to avoid resource-level N+1 for standard product list rendering.
- Show loads media and catalog relations.
- Store/update support optional image media through Spatie Media Library.
- Delete is soft delete through repository and shared trash handling.

### 3. Excel and trash flows

- Products support Excel template/export/import through `ProductExcelService`.
- Products support trash, restore, force-delete, and bulk trash actions via shared `ManagesTrash` and repository `HandlesTrash`.
- `Product` uses `DeletesMediaOnForceDelete`, so media cleanup is tied to force delete.

## Fixes applied in this pass

### 1. Fixed product search filter key

`ProductController::index` accepted `name`, but `ProductFilter` implements `search`. The accepted filters now include `search`, so product search by name/SKU can actually run.

### 2. Returned product resources from create/update

Product create and update now return `ProductResource` with media and catalog relations loaded instead of generic message-only responses. This keeps catalog API responses consistent with the improved company create/update flow and helps frontend state sync.

### 3. Hardened product hierarchy validation

Product create/update now validate that a selected sub-brand belongs to the selected/effective brand and that a selected sub-category belongs to the selected/effective category. Selecting a sub-brand without a brand, or a sub-category without a category, is rejected.

### 4. Enforced company-scoped SKU uniqueness in requests

The products table already has a unique `(company_id, sku)` constraint. Product create/update validation now mirrors that database rule, so duplicate SKUs in the same company are rejected at validation time instead of surfacing as database errors.

### 5. Improved product Excel update/import safety

Product Excel keeps the user-facing template free of internal IDs and uses `sku` as the update key when present. If an imported row has a SKU matching an existing product in the current company, that product is updated; otherwise a new product is created. Shared catalog import validation also rejects sub-brand values without a brand and sub-category values without a category, mirroring Product API validation in Excel imports.

### 6. Enforced backend Excel import row limit and clearer errors

Catalog Excel import now rejects files over 1000 rows at the backend level, matching the template validation range. Row-level errors can also include a `column` key so the frontend can point users to the specific field that needs fixing.

### 7. Product delete policy with tasks

Products can be deleted from the company catalog normally. Soft delete hides them from future catalog usage, and force delete is allowed to permanently remove the catalog product. If the product is linked to task-service-products, the database cascade removes those product links from the task; we do not keep catalog snapshots on tasks. Services remain the stable task definition and are not deleted by catalog cleanup.


### 8. Cascaded trash actions for catalog parents

Catalog parent delete/restore/force-delete actions now cascade to their catalog children so the visible catalog tree stays consistent. Children deleted by a parent cascade are stamped with parent metadata, so parent restore only restores the children removed by that parent action and does not resurrect children that users had already deleted manually:

- Brand actions cascade to sub-brands, categories, sub-categories, and products.
- Sub-brand actions cascade to categories, sub-categories, and products.
- Category actions cascade to sub-categories and products.
- Sub-category actions cascade to products.
- Cascade restore is marker-aware: it clears the parent delete marker only for children it restores.

This keeps the company catalog behavior aligned with the product delete decision: catalog records can be removed from the company catalog without preserving task snapshots, while services remain the stable task definition.

## Issues / gaps found

### P1 - Excel import still needs a broader performance/UX pass

Product Excel import can create or update many records and is high risk for partial data and memory issues.

**Suggested fix:** review chunking and import transaction behavior in a dedicated pass.

## Proposed implementation plan

### Step 1 - Product validation hardening

1. Add tests for product hierarchy validation.
2. Add tests for company-scoped SKU uniqueness validation.

### Step 2 - Product Excel review

1. Review import chunk strategy.
2. Confirm transaction behavior for mixed valid/invalid rows.
3. Add Excel import tests for SKU-based updates, hierarchy mismatch, row limit, and duplicate SKU behavior.

### Step 3 - Product tests needed

1. Company cannot create product with brand from another company.
2. Company cannot create product with mismatched brand/sub-brand.
3. Company cannot create product with mismatched category/sub-category.
4. Product create/update returns full `ProductResource`.
5. Product index search filters by product name/SKU.
6. Force deleting product removes associated media and catalog product links from tasks.

## Suggested next action

Continue with the Product Excel flow before applying the same catalog review pattern to Brands, SubBrands, Categories, and SubCategories.
