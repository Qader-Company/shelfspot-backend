# Redis caching plan

## Goal and scope

This document defines the first Redis caching rollout for the ShelfSpots API. It is deliberately limited to expensive, frequently-read data that can tolerate a short period of staleness. It does not cache real-time task, wallet, notification, or authorization data.

All API cache keys use the `v1` namespace. Laravel's `CACHE_PREFIX` supplies the application/environment prefix, so keys in this document do not repeat it.

## Shared policy

| Concern | Decision |
| --- | --- |
| Cache store | Redis, using the `cache` connection and `REDIS_CACHE_DB=1` |
| Read strategy | Cache-aside. Read Redis first; on a miss, calculate from MySQL and store the response array. |
| Dashboard strategy | Stale-while-revalidate using `Cache::flexible`. This prevents a burst of expensive requests when a key expires. |
| Cached value | Plain PHP arrays / serialized API payload data only. Do not cache Eloquent models, query builders, request objects, or paginator instances. |
| Key format | `v1:{domain}:{scope}:{variant}`. Include every input that changes the result: tenant/company, period, filters, page, and locale where relevant. |
| Invalidation | Explicit targeted invalidation after the write transaction commits. Never use `Cache::flush()`. TTL is a fallback, not the primary consistency mechanism. |
| Transaction safety | Register invalidation through `DB::afterCommit()` or an equivalent after-commit domain event. A rolled-back write must not invalidate or repopulate cache. |
| Cache stampede | Use `Cache::flexible` for dashboards. For any future expensive `Cache::remember` workload, add a short Redis lock before rebuilding it. |
| Failure mode | A Redis failure must fall back to the database and be logged/monitored; it must not make a read endpoint unavailable. |
| Locale | Include `app()->getLocale()` in keys whenever translated labels/names are present. |

## Target endpoints

### Phase 1 — dashboards

| Endpoint | Why cache it | Key | Technique and lifetime | Invalidate after |
| --- | --- | --- | --- | --- |
| `GET /api/v1/admin/dashboard?period={week|month|year}` | Aggregates tasks, companies, workers, wallet transactions, and assignments across the platform. It already has a one-minute cache and is the first Redis pilot. | `v1:dashboard:admin:{period}:{locale}` | `Cache::flexible`; fresh for 60 seconds, stale for 5 minutes. | Commit of a create/update/delete/restore affecting `Task`, `Company`, `Worker`, `TaskWorkerAssignment`, or `CompanyWalletTransaction`. |
| `GET /api/v1/company/reports/dashboard?period={week|month|year}` | Currently executes several aggregate task queries on every request. Each company has an independent result. | `v1:dashboard:company:{companyId}:{period}:{locale}` | `Cache::flexible`; fresh for 60 seconds, stale for 5 minutes. | Commit of a task, assignment, or wallet transaction belonging to that company. Only clear that company’s keys. |

Notes:

- The admin cache invalidation already exists in `AppServiceProvider`; it will be moved behind an after-commit invalidation service.
- The company dashboard must not be cleared globally when one company changes.
- A period is always normalized to `week`, `month`, or `year` before building the key.

### Phase 2 — reference/configuration data

| Endpoint | Why cache it | Key | Technique and lifetime | Invalidate after |
| --- | --- | --- | --- | --- |
| `GET /api/v1/company/products/filter-options?brand_id=&sub_brand_id=&category_id=&sub_category_id=` | Resolves multiple translated catalog queries and is commonly requested while creating/editing a task. The result is small and catalog data changes relatively infrequently. | `v1:products:filter-options:company-{companyId}:revision-{n}:locale-{locale}:...` | `Cache::remember`; 15 minutes; company revision in the key. | Commit of create/update/delete/restore/import affecting `Brand`, `SubBrand`, `Category`, `SubCategory`, their translations, or a catalog relationship used by the filter. |
| `GET /api/v1/admin/platform-settings` | Global settings are read repeatedly and have one controlled write endpoint. | `v1:platform-settings:locale-{locale}` | `Cache::remember`; 1 hour. | Commit of a platform-settings write; clears the supported locale variants. |
| `GET /api/v1/company/services` and `GET /api/v1/admin/services` | Small, mostly stable service catalog used across task workflows. | `v1:services:catalog:revision-{n}:locale-{locale}:active-{all|true|false}` | `Cache::remember`; 30 minutes; global catalog revision in the key. | Commit of a service or service translation create/update/delete. |

Product filter options use a company-scoped revision key. Every catalog mutation increments the revision after commit; the next read uses a new response key while old variants expire naturally after their TTL. This avoids wildcard deletion while covering every filter combination. If list-page caching is introduced later, use the same version-key pattern or Redis cache tags.

### Phase 3 — evaluate after measuring production usage

These endpoints are candidates, not part of the initial implementation. They should be cached only after measuring repeated requests, query time, response size, and Redis memory use.

| Endpoint group | Proposed key dimensions | Proposed lifetime | Invalidation |
| --- | --- | --- | --- |
| `GET /api/v1/company/products` and admin company product catalog pages | company ID, filters, page, per-page, locale | 5–10 minutes | Product, catalog relation, translation, media, import, restore, or delete change |
| Company/admin brand, sub-brand, category, and sub-category list pages | tenant/company, filters, page, locale | 10 minutes | Corresponding catalog write/import/restore/delete |

These responses are paginated and may have many filter combinations. We will cap supported `per_page`, monitor key cardinality, and avoid caching search terms until metrics justify it.

## Explicitly excluded from Redis response caching

| Endpoint/data group | Reason |
| --- | --- |
| Company and worker task lists/details | Task status, assignment, deadlines, and progress change frequently. The new lightweight list resource and SQL indexes are safer first optimizations. |
| `GET /api/v1/worker/tasks/nearby` | Depends on current worker location, radius, availability, and task assignment. Stale results are harmful. |
| Wallet balance and transaction endpoints | Financial data must reflect committed state immediately. |
| Notifications | Read/unread state is user-specific and changes in real time. |
| Authentication, OTP, profiles, permissions, and access-control responses | Security-sensitive and user-specific. The permission package manages its own cache where applicable. |
| Public enum endpoints | They return PHP enum values; they do not read MySQL, so Redis would add overhead rather than remove it. HTTP cache headers can be considered separately. |

## Invalidation ownership

Cache invalidation must live in application/domain services or model observers, not be duplicated in controllers. The owner of a mutation knows the exact affected company/catalog entity and calls the corresponding invalidator after commit.

Initial invalidator responsibilities:

| Invalidator | Clears |
| --- | --- |
| `AdminDashboardCache` | All three admin dashboard periods. |
| `CompanyDashboardCache` | The three periods for one company ID. |
| `ProductFilterOptionsCache` | Cached options affected by catalog hierarchy changes. |
| `PlatformSettingsCache` | The global platform-settings key for every supported locale. |
| `ServiceCatalogCache` | Advances the global services-catalog revision so every filter and locale variant becomes unreachable. |

## Delivery order and acceptance criteria

1. **Redis verification** — configure `CACHE_STORE=redis`, clear config, and prove a cache key is written/read from Redis.
2. **Cache foundation** — add TTL configuration, key builders, after-commit invalidation helpers, and cache hit/miss logging.
3. **Admin dashboard** — migrate its existing cache to the shared foundation; test hit, miss, and invalidation after a committed task change.
4. **Company dashboard** — add company-scoped stale-while-revalidate cache; test that a change in company A does not clear company B.
5. **Product filter options** — add cache and catalog invalidation tests, including translated responses.
6. **Platform settings and services** — implemented with update invalidation and revisioned service keys.
7. **Measure and decide** — review hit rate, p95 latency, query count, Redis memory, and cache key count before caching catalog list pages.

Each cached endpoint needs tests for cache miss, cache hit, key isolation, invalidation after commit, and a Redis-unavailable fallback.
