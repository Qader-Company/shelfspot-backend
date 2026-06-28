# Flow Review: Companies + Company Admins + ShelfSpot Admins

## Scope

### Portals

- Admin portal company management: create, list, show, update, soft delete, trash, restore, force delete.
- Public/company registration path that creates a company with its owner.
- Company portal access-control admins management.
- Admin portal ShelfSpot admins management.

### Main files

- `routes/V1/admin/companies.php`
- `routes/V1/admin/access-control.php`
- `routes/V1/company/access-control.php`
- `app/Modules/V1/Companies`
- `app/Modules/V1/CompanyAdmins`
- `app/Modules/V1/Admins`
- `app/Modules/V1/AccessControl/Infrastructure/Persistence/Repositories/EloquentManagedAdminRepository.php`
- `app/Modules/V1/Companies/Application/UseCases/CreateCompanyWithOwnerUseCase.php`
- `app/Modules/V1/CompanyAdmins/Application/UseCases/CreateCompanyUserUseCase.php`
- `app/Modules/V1/AccessControl/Application/Services/FullAccessRoleProvisioner.php`

## Current implementation summary

### 1. Company creation

- Admin company creation and public company registration both use `CreateCompanyWithOwnerUseCase`.
- Company creation happens inside a DB transaction.
- Owner company-user is created with `is_owner = true`.
- Full-access company owner role is assigned after company-user creation.

### 2. Company listing/details/update/delete

- Admin can list companies with filters: `search`, `active`, `industry`.
- Admin can show company details with derived metrics such as task counts, total spending, latest tasks, and product count.
- Admin can update company profile fields and activation status.
- Admin can soft-delete companies and use shared trash/restore/force-delete actions.

### 3. Company admins

- Company tenant can list, create, update, and delete company admins through company access-control routes.
- Roles assigned to company admins are scoped by `portal = company` and current tenant `company_id`.
- Owner role is protected from manual assignment/deletion.

### 4. ShelfSpot admins

- Admin portal can list, create, update, and delete ShelfSpot admins.
- Roles assigned to ShelfSpot admins are scoped by `portal = admin` and `company_id = null`.
- Super-admin role is protected from manual assignment/deletion.

## Fixes applied in this pass

### 1. Returned company resources from create/update

Company create now returns `201` with `CompanyResource`, and company update returns the updated `CompanyResource` instead of generic message-only responses. This improves API consistency and frontend state sync.

### 2. Fixed company details users relation output

`CompanyController::show` loads the `users` relation, but `CompanyResource` was reading `whenLoaded('companyUsers')`, which is not the relation name on the `Company` model. The resource now reads `whenLoaded('users')` so company owner/admin users can appear in show responses.

### 3. Scoped admin email uniqueness on update by portal type

Admin update requests now validate email uniqueness only against `users` with `type = admin`, matching the admin create validation and the login model.

### 4. Scoped company-admin email uniqueness on update by portal type

Company admin update requests now validate email uniqueness only against `users` with `type = company`, matching company portal identity rules and company-admin create validation intent.

## Issues / gaps found

### P1 - Company active/deleted state side effects need explicit policy

`is_active = false` blocks company users through activation checks, but soft-deleting a company and its effects on active sessions, catalog visibility, and running tasks need a clearly documented product rule.

**Suggested fix:** define policy for active/inactive/deleted companies and add tests around login/access/task behavior.

### P1 - Company admin deletion deletes the shared user row

`deleteCompanyAdmin` deletes the `company_users` row and then deletes the `users` row. This is correct if company users are strictly single-company accounts. If future multi-company users are allowed, this will need to become relation deletion/deactivation only.

**Suggested fix:** document single-company-user assumption or refactor to support multi-company identities.

### P2 - Admin/company-admin update password rule is weaker than registration

Registration requires stronger password rules, while admin update uses only `min:8`. This may be acceptable for managed-admin workflows, but it is inconsistent.

**Suggested fix:** use the same `Password::min(8)->mixedCase()` rule unless product requires simpler admin-created temporary passwords.

### P2 - Company slug is generated only on create

Company slug includes name, industry, CR number, and random suffix. Updating name/industry/CR does not update slug. This is safer for stable tenant identifiers, but should be explicit.

**Suggested fix:** document slug immutability as intended, or add controlled slug regeneration if product wants it.

## Proposed implementation plan

### Step 1 - Lifecycle policy

1. Document behavior for inactive company.
2. Document behavior for soft-deleted company.
3. Decide whether active tokens should be revoked when a company is disabled.
4. Decide what happens to running tasks when company is disabled/deleted.

### Step 2 - Tests needed

1. Admin create company creates owner user and assigns owner role.
2. Company show returns owner/admin users.
3. Company inactive blocks company user login/access.
4. Company admin cannot update/delete admin outside current tenant.
5. Company owner cannot be deleted by company admin management.
6. ShelfSpot super-admin cannot be deleted.

## Suggested next action

Add the company creation/show/update tests, then define the inactive/deleted company lifecycle policy before moving to the Catalog family flow.
