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

## Issues / gaps found

### P1 - Product relationship consistency is only company-scoped, not hierarchy-scoped

Current validation ensures selected IDs belong to the current company, but it does not prove that `sub_brand_id` belongs to the selected `brand_id`, or that `sub_category_id` belongs to the selected `category_id`.

**Suggested fix:** add cross-field validation after base rules to ensure selected sub-brand/category hierarchy is internally consistent.

### P1 - SKU uniqueness policy is unclear

`sku` is optional and not unique. If product SKU should identify company catalog products, uniqueness should be enforced per company.

**Suggested fix:** decide whether SKU is optional metadata or a company-scoped unique identifier. If unique, add validation and a database unique index for `(company_id, sku)` where SKU is not null where supported.

### P1 - Excel import needs a dedicated pass

Product Excel import can create or update many records and is high risk for partial data, relationship mismatch, and memory issues.

**Suggested fix:** review `ProductExcelService`, `ProductImport`, and shared catalog Excel abstractions in the next catalog sub-pass.

### P2 - Product delete policy with tasks needs confirmation

Soft-deleted products are hidden from normal catalog queries, but old tasks may reference products through task-service-products snapshots/relations.

**Suggested fix:** confirm that deleting a product only prevents future task usage and does not mutate historical task data.

## Proposed implementation plan

### Step 1 - Product validation hardening

1. Add hierarchy validation: sub-brand must belong to brand when both are supplied.
2. Add hierarchy validation: sub-category must belong to category when both are supplied.
3. Decide and enforce SKU uniqueness policy.

### Step 2 - Product Excel review

1. Review template columns and required fields.
2. Review import validation and row-level errors.
3. Confirm import transaction/chunk strategy.
4. Confirm relationship resolution respects tenant and hierarchy.

### Step 3 - Product tests needed

1. Company cannot create product with brand from another company.
2. Company cannot create product with mismatched brand/sub-brand.
3. Company cannot create product with mismatched category/sub-category.
4. Product create/update returns full `ProductResource`.
5. Product index search filters by product name/SKU.
6. Force deleting product removes associated media.

## Suggested next action

Harden product hierarchy validation first, then continue with the Product Excel flow before applying the same catalog review pattern to Brands, SubBrands, Categories, and SubCategories.
