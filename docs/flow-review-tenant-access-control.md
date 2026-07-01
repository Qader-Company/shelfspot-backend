# Flow Review: Tenant + Access Control

## Scope

### Portals

- Company portal routes that require `tenant` and `tenant.user` middleware.
- Admin portal routes that manage a selected company through `tenant.route-company`.
- Admin and company access-control APIs for permissions, roles, and managed admins.

### Main files

- `config/modules.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Middleware/SetTenantFromHeader.php`
- `app/Http/Middleware/SetTenantFromRouteCompany.php`
- `app/Http/Middleware/EnsureTenantUser.php`
- `app/Http/Middleware/CheckScopedPermission.php`
- `app/Http/Middleware/CheckScopedRole.php`
- `app/Modules/Shared/Infrastructure/Tenant/TenantContext.php`
- `app/Modules/V1/AccessControl`
- `app/Modules/V1/Admins/Presentation/Http/Controller/ShelfSpotAdminManagementController.php`
- `app/Modules/V1/CompanyAdmins/Presentation/Http/Controllers/CompanyAdminManagementController.php`
- `routes/V1/admin/access-control.php`
- `routes/V1/company/access-control.php`

## Current implementation summary

### 1. Route registration

- `AppServiceProvider` loads route groups from `config/modules.php`.
- Every API route receives `api`, `locale`, and `api.key` middleware.
- Company catalog/wallet/task/access-control routes receive `auth:sanctum`, `abilities:company,access`, `tenant`, and `tenant.user`.
- Admin routes receive `auth:sanctum`, `abilities:admin,access`.
- Admin-managed company catalog routes receive `tenant.route-company` to set tenant context from `{company}`.

### 2. Tenant resolution

- Company portal tenant context is read from `X-Company-Slug`.
- Admin-managed tenant context is read from route parameter `{company}`.
- `TenantContext` stores the selected `Company`, slug, and company id in request-scoped singleton state and `config('tenant.*')`.

### 3. Tenant user protection

- `EnsureTenantUser` requires an authenticated company user.
- It verifies the user belongs to the selected company, the company-user relation is active, and the company is active.

### 4. Scoped permission/role checks

- `CheckScopedPermission` maps user type to portal: admin or company.
- Company users are checked against roles with `company_id = current tenant company id`.
- Admin users are checked against roles with `company_id = null`.
- Permission lookup also checks permission portal.

### 5. Access-control management

- Admin access-control manages global admin roles/admin users.
- Company access-control manages roles/admin users for the current tenant.
- Protected roles `super_admin` and `owner` are blocked from manual create/update/delete/assignment in the repository layer.

## Fixes applied in this pass

### 1. Added explicit tenant guard to scoped role/permission middleware

Company users now fail immediately if scoped permission/role middleware is reached without a resolved tenant company id. This keeps company authorization from accidentally falling back to `company_id = null`.

### 2. Cleared Spatie permission cache after role mutations

Role create/update/delete operations now clear Spatie permission cache after changing permissions or deleting roles, keeping runtime authorization aligned with management APIs.

### 3. Fixed broken CompanyUser namespace in managed-admin repository

`EloquentManagedAdminRepository` imported `CompanyUser` from a non-existent `CompanyUsers` module. It now imports the actual company-admin model namespace.

### 4. Added missing company-admin destroy endpoint handler

`routes/V1/company/access-control.php` exposes `DELETE /admins/{user}` mapped to `destroyAdmin`, but `CompanyAdminManagementController` did not implement the method. The method now delegates to `deleteCompanyAdmin` using the current tenant company id and returns the standard deleted response.

## Issues / gaps found

### P0 - Company admin delete route existed without controller implementation

This was an actual runtime bug and was fixed in this pass.

### P0 - Managed-admin repository had a non-existent CompanyUser namespace

This was an actual runtime bug affecting company-admin create/update/delete flows and was fixed in this pass.

### P1 - Tenant resolution from header does not itself enforce active company

`SetTenantFromHeader` resolves companies without global scopes and accepts any company found by slug. `EnsureTenantUser` later checks active company for protected company routes, so current protected routes are covered. The risk is future routes using only `tenant` without `tenant.user`.

**Suggested fix:** either move active-company validation into `SetTenantFromHeader`, or document that `tenant` must never be used alone on company portal protected routes unless inactive tenants are intentionally allowed.

### P1 - Route model binding for `{user}` is not portal-constrained at route level

Update/delete managed admin endpoints accept `User $user`, and repository methods validate portal/company membership afterward. This is safe enough because repository checks exist, but route-level constraints/policies would produce clearer 404/403 behavior earlier.

**Suggested fix:** add explicit FormRequest/policy checks or scoped binding helpers for admin/company managed users.

### P2 - Repeated permission catalog sync on reads

Permission listing and role listing call `PermissionCatalog::sync($portal)` on every read. This is safe/idempotent, but it adds write-oriented work to read endpoints.

**Suggested fix:** move permission sync to seeders/deploy/startup command and keep read endpoints read-only, or cache the sync status.

### P2 - TenantContext also writes to global config

`TenantContext::setCompany` writes `config(['tenant.*' => ...])`. In normal PHP-FPM request lifecycle this is request-local, but it is easier to reason about if consumers depend on the context interface rather than global config.

**Suggested fix:** prefer injecting `TenantContextInterface` everywhere and gradually remove direct `config('tenant.*')` dependencies if any exist.

## Proposed implementation plan

### Step 1 - Finish safety fixes

1. Add tests for missing tenant context on company permission checks.
2. Add tests for permission cache invalidation after role changes.
3. Review whether route-level policies should constrain `{user}` bindings earlier.

### Step 2 - Add access-control feature tests

1. Company admin from company A cannot use company B roles.
2. Company admin cannot assign `owner` role manually.
3. Admin cannot create/delete `super_admin` role manually.
4. Company `DELETE /access-control/admins/{user}` deletes only admins inside same tenant.
5. Inactive company user is forbidden even with valid token and role.

### Step 3 - Improve tenant contract

1. Decide whether inactive companies can ever be selected by `tenant` middleware.
2. If not, enforce active company in tenant resolution.
3. Keep admin `tenant.route-company` behavior flexible if admins must manage inactive/deleted companies.

## Suggested next action

Before moving to Catalog, I recommend applying Step 1 because it is small and directly improves the security boundary for all company routes.
