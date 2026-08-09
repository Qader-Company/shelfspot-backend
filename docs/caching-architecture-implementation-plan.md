# Redis caching architecture and implementation plan

## Purpose

This plan describes how ShelfSpots will introduce Redis caching without coupling business modules to Laravel's cache facade or Redis-specific details. The design follows the existing module layout: shared abstractions live in `Shared`, while cache policies and invalidation rules remain owned by the module whose data is cached.

This document is the implementation companion to [caching-plan.md](caching-plan.md), which lists the endpoint-level caching decisions.

## Design principles

1. **Caching is an application/infrastructure concern.** Domain models and use cases do not know about Redis.
2. **Modules own their cache policy.** A reports cache belongs to `Reports`; a catalog cache belongs to its catalog module.
3. **The shared layer owns the mechanism.** Key storage, TTL execution, stale-while-revalidate, Redis failures, and after-commit dispatching are implemented once.
4. **Writes invalidate after commit.** A rolled-back transaction must never alter cache state.
5. **Every key has an owner and a test.** No anonymous cache keys or controller-level invalidation.
6. **Redis failure degrades to the source of truth.** Read endpoints continue from MySQL when cache infrastructure is unavailable.
7. **Caching is configurable.** Each cache group can be disabled through configuration without changing application code.

## Target architecture

```text
Shared/
  Application/Caching/
    Contracts/
      CacheStoreInterface.php
      AfterCommitDispatcherInterface.php
    Data/
      CacheTtl.php
    Services/
      CacheInvalidationDispatcher.php
      CacheVersionManager.php

  Infrastructure/Caching/
    LaravelCacheStore.php
    LaravelAfterCommitDispatcher.php
    Providers/
      CachingServiceProvider.php

Reports/
  Application/Caching/
    AdminDashboardCache.php
    CompanyDashboardCache.php
  Infrastructure/Listeners/
    ReportCacheInvalidator.php

Products/
  Application/Caching/
    ProductFilterOptionsCache.php

PlatformSettings/
  Application/Caching/
    PlatformSettingsCache.php
  Application/Services/
    PlatformSettingsService.php

Services/
  Application/Caching/
    ServiceCatalogCache.php
  Application/Services/
    ServiceCatalogService.php
```

### Responsibilities

| Component | Responsibility |
| --- | --- |
| `CacheStoreInterface` | Stable application-facing cache port: `remember`, `flexible`, and targeted removal. |
| `CacheTtl` | Validated immutable definition of fresh and stale durations. |
| `LaravelCacheStore` | Redis/Laravel adapter. It handles cache-driver failures and falls back to the resolver without hiding source-of-truth failures. |
| `AfterCommitDispatcherInterface` | Application port for scheduling callbacks after the current transaction commits. |
| `LaravelAfterCommitDispatcher` | Laravel database adapter for the after-commit port. |
| `CacheInvalidationDispatcher` | Schedules deduplicated targeted invalidation through the two shared ports. |
| `CacheVersionManager` | Maintains revision keys for cache groups with too many filter combinations to remove individually. |
| Module cache classes | Build keys, expose policy, and know exactly which keys must be invalidated. |
| Module invalidators/listeners | Observe successful writes and call the relevant module cache class after commit. |

## Key and configuration conventions

Keys will use this format:

```text
v1:{module}:{resource}:{scope}:{variant}
```

Examples:

```text
v1:reports:admin-dashboard:week:ar
v1:reports:company-dashboard:42:month:en
v1:products:filter-options:ar:brand-8:sub-brand-none:category-12
```

`CACHE_PREFIX` remains responsible for the deployment/environment boundary. The application key must contain all values that change a response: API version, tenant/company, filters, pagination, and locale as appropriate.

The `shelfspot_cache` configuration file will contain:

- the cache store name;
- a global enable/disable flag;
- feature flags for reports and catalog cache groups;
- fresh/stale dashboard durations;
- normal reference-data TTLs.

No TTL values will be hard-coded in controllers or business services.

## Delivery phases

### Phase 1 — shared caching foundation

Deliverables:

1. `shelfspot_cache` configuration and Redis store selection.
2. Shared contracts, immutable `CacheTtl`, and Laravel adapters.
3. `CachingServiceProvider`, registered through the existing modular provider list.
4. After-commit invalidation dispatcher.
5. Unit tests for TTL validation, deferred invalidation, deduplication, and container bindings.

Out of scope:

- No endpoint reads from Redis yet.
- No existing endpoint response contract changes.
- No model observer changes beyond the shared foundation.

Acceptance criteria:

- Modules can depend on shared contracts without importing Laravel's `Cache` or `DB` facades.
- A cache removal requested inside a transaction is executed only after commit.
- A cache group can be disabled through configuration.

### Phase 2 — admin dashboard pilot

Deliverables:

1. Refactor `AdminDashboardCache` to use the shared key/policy abstractions.
2. Replace direct Laravel cache usage in `AdminDashboardService` with `CacheStoreInterface`.
3. Move dashboard invalidation out of `AppServiceProvider` into the Reports module.
4. Use stale-while-revalidate: 60-second fresh window and 5-minute stale window.
5. Add hit, miss, invalidation-after-commit, and rollback tests.

Acceptance criteria:

- A repeated request during the fresh window executes no dashboard aggregate queries.
- A committed relevant write clears all affected admin-dashboard periods.
- A rollback leaves the existing cache entry intact.

### Phase 3 — company dashboard

Deliverables:

1. Add `CompanyDashboardCache` with company-scoped keys.
2. Add `ReportCacheInvalidator` rules that clear only the affected company.
3. Reuse the phase-1 shared store and invalidation dispatcher.

Acceptance criteria:

- A write for company A does not invalidate company B's dashboard cache.
- The endpoint has the same stale-while-revalidate behavior as the admin dashboard.

### Phase 4 — reference data

Deliverables:

1. Cache `ProductFilterOptionsService` results with a company-scoped revision.
2. Cache platform settings per locale and invalidate all locale variants after commit.
3. Cache the services catalog by locale and active filter with a global revision.
4. Advance catalog revisions after relevant CRUD writes, so old variants expire naturally without wildcard deletion.

`ProductFilterOptionsService` uses a company-scoped revision key. Catalog changes increment this revision after commit; filter responses include it in their cache key, so all affected filter variants become unreachable immediately without wildcard deletion. Old values expire with their normal TTL.

Acceptance criteria:

- Repeating an identical filter-options request reads from Redis.
- A catalog mutation invalidates the impacted locale-aware options.
- A platform-settings mutation removes every locale variant only after commit.
- A service or service-translation mutation advances the services-catalog revision only after commit.
- No globally unrelated keys are removed.

### Phase 5 — measure catalog lists before caching

Deliverables:

1. Measure request rate, SQL duration, payload size, cache key cardinality, and hit rate.
2. Decide whether paginated catalog list responses warrant caching.
3. If approved, add bounded page/filter/locale keys and cache-tag or version-key invalidation.

Acceptance criteria:

- Pagination cache has a documented memory bound.
- Search terms are not cached unless observed reuse justifies them.

## Testing strategy

Every cache policy requires these tests:

1. **Miss:** resolver runs and stores the returned array.
2. **Hit:** resolver does not run again.
3. **Key isolation:** a different tenant, period, filter, or locale cannot read another variant.
4. **Invalidation:** a committed relevant write removes only its target keys.
5. **Rollback:** a rolled-back transaction does not remove valid cache.
6. **Failure mode:** a cache-driver failure falls back to the database resolver.

## Rollout and rollback

1. Deploy Phase 1 with cache feature flags disabled; verify Redis connectivity and logs.
2. Enable the reports flag for the admin dashboard only.
3. Monitor hit rate, p95 response time, Redis memory, and error logs.
4. Enable subsequent phases one cache group at a time.
5. Disable the related feature flag immediately if stale or cache-driver behavior is detected; MySQL remains the source of truth.
