# Project: ShelfSpot

## Description
A Laravel API platform for managing retail service tasks, company catalogs, workers, wallets, and portal-specific access control.

## Business Idea
ShelfSpot connects companies that need retail execution services with workers who can discover, accept, and complete nearby tasks. Companies manage product catalogs, service requests, wallet activity, and internal admins from a tenant-scoped portal. ShelfSpot admins oversee companies, workers, services, permissions, catalog data, coupons, and task lifecycle operations across the platform.

## Tech Stack
- **Backend:** PHP 8.3, Laravel 13, Laravel Sanctum, Spatie Permission, Spatie Media Library, Laravel Translatable, Laravel OTP, Laravel API Key, Maatwebsite Excel
- **Frontend:** Vite, Tailwind CSS, Laravel Blade
- **Database:** SQLite by default, configurable through Laravel database connections

## Links

## Architecture
ShelfSpot is organized as a versioned Laravel API with most domain code under `app/Modules/V1`. Modules commonly follow layered boundaries for Presentation controllers, requests, and resources; Application use cases, services, validation, and Excel handling; Domain models, repositories, enums, and value objects; and Infrastructure repositories and service providers. API entry points are split by portal rather than kept directly in `routes/api.php`, with `routes/V1/public`, `routes/V1/admin`, `routes/V1/company`, and `routes/V1/worker` loaded through module configuration.

The platform exposes separate public, admin, company, and worker portals. Public routes handle authentication, OTP, password reset, and enums; admin routes manage platform-level entities such as companies, workers, services, catalog records, coupons, tasks, and ShelfSpot admins; company routes manage tenant-scoped catalog data, wallets, tasks, services, admins, roles, and permissions; worker routes support account updates, location, task discovery, and task execution. Laravel Sanctum token abilities, scoped permissions, API keys, tenant middleware, queues, soft deletes, media handling, Excel import/export, and database-backed cache/session/job tables support the core workflows.
