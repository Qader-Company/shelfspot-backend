# ShelfSpot Project Modules & Flows Review Map

## الهدف من المستند

هذا المستند يقسم المشروع الحالي إلى **Modules** واضحة، ثم يقسم كل Module إلى **Flows** قابلة للمراجعة واحدة واحدة في الجلسات القادمة. الهدف ليس إصلاح الكود الآن، بل تجهيز خريطة مراجعة عملية تساعدنا نمسك كل Flow ونفحص:

- الناقص وظيفيًا مقارنة بالمتطلبات.
- أي تطبيق خاطئ أو inconsistent بين الـ portals.
- مشاكل الـ validation، الـ authorization، والـ tenant isolation.
- فرص تحسين الأداء وتقليل N+1 queries أو duplicated logic.
- فرص تحسين التصميم وفصل المسؤوليات.

## نظرة عامة على المعمارية الحالية

المشروع Laravel API مقسم بشكل رئيسي إلى `app/Modules/V1`، وكل Module غالبًا يحتوي على طبقات:

- `Presentation`: controllers, requests, resources.
- `Application`: use cases, services, validation, excel handling.
- `Domain`: models, repositories interfaces, enums/value objects.
- `Infrastructure`: repositories implementations, service providers.

الـ routing لا يعتمد على `routes/api.php` مباشرة لمعظم الـ API؛ بل يتم تحميل ملفات `routes/V1/{portal}` من خلال `AppServiceProvider` بناءً على `config/modules.php`.

## Portals / Entry Points

| Portal | Prefix عام | الغرض | Middlewares أساسية |
| --- | --- | --- | --- |
| Public | `/api/v1/auth`, `/api/v1/enums` | التسجيل، الدخول، OTP، reset password، enums عامة | `api`, `locale`, `api.key` |
| Admin | `/api/v1/admin/*` | إدارة ShelfSpot: شركات، عمال، خدمات، صلاحيات، كتالوجات الشركات، Tasks | `auth:sanctum`, `abilities:admin,access`, permissions |
| Company | `/api/v1/company/*` | إدارة كتالوج الشركة، المحافظ، tasks، admins وصلاحيات الشركة | `auth:sanctum`, `abilities:company,access`, `tenant`, `tenant.user`, permissions |
| Worker | `/api/v1/worker/*` | حساب العامل، الموقع، البحث عن tasks، تنفيذ task | `auth:sanctum`, `abilities:worker,access` |

## تقسيم الـ Modules

### 1. Authentication Module

**المسؤولية:** تسجيل ودخول المستخدمين وإصدار tokens، OTP، تفعيل البريد، reset password.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/Authentication`
- `routes/V1/public/auth.php`
- `app/Modules/V1/Users`
- `app/Modules/V1/Companies/Application/UseCases/CreateCompanyWithOwnerUseCase.php`
- `app/Modules/V1/Workers/Application/UseCases/CreateWorkerUseCase.php`

**Flows للمراجعة:**

1. Company registration + owner creation.
2. Worker registration.
3. Admin/company/worker login.
4. Access token vs refresh token issuing.
5. Logout and refresh token revocation.
6. Send OTP for email verification/reset password.
7. Verify email.
8. Verify reset-password OTP.
9. Reset password.

**نقاط مراجعة مقترحة:** rate limiting، uniqueness checks، token abilities، OTP expiry/reuse، consistent response shape، فصل portal-specific logic.

### 2. Users / Portal Identity Module

**المسؤولية:** تمثيل user account عام وربطه بنوع portal وإرجاع resource مناسب.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/Users`
- `app/Modules/V1/CompanyAdmins`
- `app/Modules/V1/Admins`
- `app/Modules/V1/Workers`

**Flows للمراجعة:**

1. Resolve authenticated user resource حسب portal.
2. Check active/blocked/deleted user.
3. Mapping بين `users` و `company_users` و `shelf_spot_admins` و `workers`.
4. Portal scoping للـ roles/permissions.

### 3. Access Control Module

**المسؤولية:** roles, permissions, scoped permissions بين admin portal و company portal.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/AccessControl`
- `routes/V1/admin/access-control.php`
- `routes/V1/company/access-control.php`
- `app/Http/Middleware/CheckScopedPermission.php`
- `app/Http/Middleware/CheckScopedRole.php`
- `database/seeders/AccessControlPermissionSeeder.php`

**Flows للمراجعة:**

1. Permission catalog generation/seeding.
2. Admin roles CRUD.
3. Company roles CRUD.
4. Assign role to admin/company admin.
5. Permission middleware resolution.
6. Full-access role provisioning.

**نقاط مراجعة مقترحة:** منع خلط permissions بين portals، tenant scoping، حماية super admin/full access من الحذف الخطأ، تحسين seeding idempotency.

### 4. Companies Module

**المسؤولية:** إدارة الشركات وبياناتها، tenant scope، owner/company creation.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/Companies`
- `routes/V1/admin/companies.php`
- `app/Http/Middleware/SetTenantFromHeader.php`
- `app/Http/Middleware/SetTenantFromRouteCompany.php`
- `app/Http/Middleware/EnsureTenantUser.php`
- `app/Modules/Shared/Infrastructure/Tenant/TenantContext.php`

**Flows للمراجعة:**

1. Admin creates company.
2. Company self-registration.
3. Company index/show/update/delete by admin.
4. Soft delete/trash/restore/force delete.
5. Tenant selection from header for company portal.
6. Tenant selection from route company for admin-managed catalog.

**نقاط مراجعة مقترحة:** tenant isolation، soft delete side effects، indexes على company filters، consistency بين admin create و self-register.

### 5. Company Admins Module

**المسؤولية:** إدارة admins داخل الشركة وصلاحياتهم.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/CompanyAdmins`
- `routes/V1/company/access-control.php`

**Flows للمراجعة:**

1. Company admin listing.
2. Create company admin.
3. Update company admin.
4. Delete/deactivate company admin.
5. Assign roles داخل نفس tenant.

### 6. ShelfSpot Admins Module

**المسؤولية:** إدارة حسابات أدمن ShelfSpot.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/Admins`
- `routes/V1/admin/access-control.php`

**Flows للمراجعة:**

1. Admin listing.
2. Create admin.
3. Update admin.
4. Delete/deactivate admin.
5. Assign admin roles.

### 7. Services Module

**المسؤولية:** كتالوج الخدمات العامة المتاحة للشركات، minimum price/time، service type.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/Services`
- `routes/V1/admin/services.php`
- `routes/V1/company/services.php`
- `database/seeders/ServiceSeeder.php`

**Flows للمراجعة:**

1. Admin CRUD services.
2. Company view services.
3. Service type validation.
4. Minimum price/execution time rules.
5. Translations/resource output.

### 8. Company Catalog Modules

تشمل modules متشابهة جدًا ويمكن مراجعتها كعائلة واحدة مع مراعاة العلاقات بينها:

- `Categories`
- `SubCategories`
- `Brands`
- `SubBrands`
- `Products`

**المسؤولية:** إدارة كتالوج منتجات الشركة مع Excel import/export، soft delete/trash، filtering، وربط hierarchical entities.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/Categories`
- `app/Modules/V1/SubCategories`
- `app/Modules/V1/Brands`
- `app/Modules/V1/SubBrands`
- `app/Modules/V1/Products`
- `app/Modules/Shared/Application/Excel`
- `app/Modules/Shared/Presentation/Http/Controllers/ManagesTrash.php`
- `app/Modules/Shared/Infrastructure/Persistence/Repositories/HandlesTrash.php`
- `routes/V1/company/{categories,sub-categories,brands,sub-brands,products}.php`
- `routes/V1/admin/catalog-*.php`

**Flows للمراجعة:**

1. CRUD category/sub-category/brand/sub-brand/product من company portal.
2. Admin manages catalog for selected company.
3. Filtering and filter-options.
4. Excel template generation.
5. Excel export.
6. Excel import.
7. Bulk delete.
8. Trash listing.
9. Bulk restore.
10. Force delete.
11. Cascading filtering بين brand/sub-brand/category/sub-category/product.

**نقاط مراجعة مقترحة:** duplicated route/controller patterns، validation لعلاقات نفس الشركة، Excel transaction handling، chunk imports، N+1 في index/export، consistency في ترتيب routes حتى لا تتعارض `trash` و `excel` مع `/{id}`.

### 9. Wallets / Coupons Module

**المسؤولية:** محفظة الشركة، ledger transactions، coupons، worker wallet/withdrawals.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/CompaniesWallets`
- `app/Modules/V1/WorkersWallets`
- `app/Modules/V1/Coupons`
- `routes/V1/company/wallets.php`
- `routes/V1/admin/wallet-coupons.php`
- `database/migrations/*wallet*`
- `database/migrations/*coupon*`

**Flows للمراجعة:**

1. Company wallet index/show.
2. Wallet recharge.
3. Coupon CRUD by admin.
4. Coupon redemption by company.
5. Wallet balance calculation.
6. Task charge/hold.
7. Task refund.
8. Worker wallet transaction creation.
9. Withdrawal request lifecycle.

**نقاط مراجعة مقترحة:** ledger as source of truth، idempotency، race conditions عند recharge/redeem/charge، locking، indexes، decimal precision.

### 10. Workers Module

**المسؤولية:** worker profile، admin management، location، distance calculation، availability.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/Workers`
- `routes/V1/admin/workers.php`
- `routes/V1/worker/account.php`
- `app/Modules/V1/Workers/Application/Services/GeoDistanceCalculator.php`
- `docs/worker-location-distance.md`

**Flows للمراجعة:**

1. Worker registration.
2. Admin creates worker.
3. Admin index/show/update/delete worker.
4. Worker profile show/update/delete.
5. Worker updates location.
6. Nearby task discovery based on worker location.
7. Soft delete/trash/restore/force delete.

**نقاط مراجعة مقترحة:** location validation، stale location rules، distance SQL performance، indexes، availability rules، privacy of worker data.

### 11. Tasks Module

**المسؤولية:** إنشاء task من الشركة، حجز/خصم wallet، ظهورها للعامل، execution lifecycle، submissions، review/accept/reject/reopen، admin reassignment.

**الملفات/المناطق الأساسية:**

- `app/Modules/V1/Tasks`
- `routes/V1/company/tasks.php`
- `routes/V1/worker/tasks.php`
- `routes/V1/admin/tasks.php`
- `docs/task-lifecycle-review-plan.md`
- `docs/task-lifecycle-api-contract.md`
- `docs/tasks-and-workers-modules-plan.md`

**Flows للمراجعة:**

1. Company task creation.
2. Task pricing and wallet charge.
3. Company task index/show/update/delete.
4. Worker nearby task list.
5. Worker starts/claims task.
6. Start execution with geofence/location validation.
7. Extend start deadline.
8. Worker submits service form/data.
9. Worker completes task.
10. Company accepts completed task.
11. Company rejects completed task with reason.
12. Review messages between admin and company.
13. Admin available workers list.
14. Admin reassign task.
15. Admin reopen rejected task.
16. Worker cancel task.
17. Expired task failure.
18. Auto accept expired review tasks.
19. Status history recording.
20. Task resource progress summary.

**نقاط مراجعة مقترحة:** atomic create + charge، status transitions centralized، race conditions في claim/reassign/complete، geofence correctness، permissions، repeated submissions after reopen، query eager loading، scheduler reliability.

### 12. Shared Infrastructure Module

**المسؤولية:** reusable traits/controllers/repositories/excel/tenant support/API response/middlewares.

**الملفات/المناطق الأساسية:**

- `app/Modules/Shared`
- `app/Facades/ApiResponse.php`
- `app/Facades/FacadesLogic/ApiResponseLogic.php`
- `app/Http/Middleware`
- `app/Providers/RateLimitServiceProvider.php`
- `app/Providers/AppServiceProvider.php`

**Flows للمراجعة:**

1. API response format.
2. Global exception mapping.
3. Locale handling.
4. API key check.
5. Rate limiting profiles.
6. Generic trash management.
7. Generic Excel import/export.
8. Tenant context lifecycle per request.

## Cross-Cutting Flows للمراجعة المستقلة

هذه flows تمر على أكثر من module ويُفضل مراجعتها بعد فهم modules الأساسية:

1. **Tenant isolation end-to-end**: company headers, route company, repository filters, validation rules.
2. **Permission enforcement end-to-end**: route middleware, scoped roles, seeded permissions.
3. **Soft delete/trash behavior**: companies, workers, catalog, task visibility.
4. **Excel import/export architecture**: validation, transactions, memory, chunking, error reporting.
5. **Wallet transaction consistency**: recharge, coupon redemption, task charge/refund, concurrency.
6. **Task lifecycle state machine**: allowed transitions and source of truth.
7. **Media/file upload lifecycle**: product images, task submissions, planograms/job orders, cleanup on force delete.
8. **Filtering/pagination performance**: model filters, indexes, eager loading, response resources.
9. **Audit/history**: task status histories and wallet ledgers.
10. **API consistency**: naming, status codes, validation errors, resource shapes.

## ترتيب مقترح للمراجعة القادمة

1. **Authentication + Users**: لأنه يحدد كل الـ access tokens والـ portal identity.
2. **Tenant + Access Control**: لأنه أساس أمان كل flows.
3. **Companies + Company Admins/Admins**: تأسيس إدارة الحسابات والـ tenant.
4. **Catalog family**: لأن Tasks تعتمد على products/catalog.
5. **Services**: لأن Tasks تعتمد على service definitions والأسعار الدنيا.
6. **Wallets/Coupons**: لأن Task creation يعتمد على charge/refund.
7. **Workers + Location**: لأن discovery/execution يعتمد عليهم.
8. **Tasks lifecycle**: أكبر flow وأعلى مخاطرة؛ تتم مراجعته بعد التأكد من dependencies.
9. **Shared/Performance pass**: refactor reusable patterns بعد معرفة مشاكل التكرار.

## Template مراجعة أي Flow

عند اختيار أي Flow في المرة القادمة، نستخدم نفس القالب:

```md
# Flow Review: <اسم الـ flow>

## Scope
- Portal(s):
- Routes:
- Controllers:
- Requests:
- Use cases/services:
- Models/tables:

## Expected Behavior
- ...

## Current Implementation Notes
- ...

## Issues / Gaps
- Functional gaps:
- Security/authorization gaps:
- Tenant isolation gaps:
- Validation gaps:
- Performance gaps:
- Code quality/refactor opportunities:

## Proposed Fix Plan
1. ...

## Tests Needed
- Feature tests:
- Unit tests:
- Edge cases:
```

## أول Flow مقترح نبدأ به

أقترح نبدأ بـ **Authentication: login/register/token flows** أو **Tenant + Access Control** قبل الدخول على Tasks، لأن أي مشكلة فيهم ستؤثر على كل modules الأخرى.

تم فتح أول مراجعة تفصيلية في `docs/flow-review-authentication.md`.
وتم فتح مراجعة **Tenant + Access Control** في `docs/flow-review-tenant-access-control.md`.
وتم فتح مراجعة **Companies + Company Admins/Admins** في `docs/flow-review-companies-admins.md`.
وتم فتح مراجعة **Catalog Products** في `docs/flow-review-catalog-products.md`.
