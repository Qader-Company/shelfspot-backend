<?php

use App\Modules\Shared\Infrastructure\Caching\Providers\CachingServiceProvider;
use App\Modules\V1\AccessControl\Infrastructure\Providers\AccessControlModuleServiceProvider;
use App\Modules\V1\Admins\Infrastructure\Providers\AdminsModuleServiceProvider;
use App\Modules\V1\Authentication\Infrastructure\Providers\AuthenticationModuleServiceProvider;
use App\Modules\V1\Brands\Infrastructure\Providers\BrandsModuleServiceProvider;
use App\Modules\V1\Categories\Infrastructure\Providers\CategoriesModuleServiceProvider;
use App\Modules\V1\Companies\Infrastructure\Providers\CompaniesModuleServiceProvider;
use App\Modules\V1\CompaniesWallets\Infrastructure\Providers\CompaniesWalletsModuleServiceProvider;
use App\Modules\V1\CompanyAdmins\Infrastructure\Providers\CompanyUsersModuleServiceProvider;
use App\Modules\V1\Coupons\Infrastructure\Providers\CouponsModuleServiceProvider;
use App\Modules\V1\Products\Infrastructure\Providers\ProductsModuleServiceProvider;
use App\Modules\V1\PlatformSettings\Infrastructure\Providers\PlatformSettingsModuleServiceProvider;
use App\Modules\V1\Reports\Infrastructure\Providers\ReportsModuleServiceProvider;
use App\Modules\V1\Services\Infrastructure\Providers\ServicesModuleServiceProvider;
use App\Modules\V1\SubBrands\Infrastructure\Providers\SubBrandsModuleServiceProvider;
use App\Modules\V1\SubCategories\Infrastructure\Providers\SubCategoriesModuleServiceProvider;
use App\Modules\V1\Tasks\Infrastructure\Providers\TasksModuleServiceProvider;
use App\Modules\V1\Users\Infrastructure\Providers\UserModuleServiceProvider;
use App\Modules\V1\Workers\Infrastructure\Providers\WorkersModuleServiceProvider;
use App\Modules\V1\WorkersWallets\Infrastructure\Providers\WorkersWalletsModuleServiceProvider;

$adminAuthMiddlewares = ['auth:sanctum', 'abilities:admin,access'];
$adminManageCompaniesMiddlewares
    = array_merge($adminAuthMiddlewares, ['tenant']);

$companyAuthMiddlewares = ['auth:sanctum', 'abilities:company,access'];
$companyMiddlewares = array_merge($companyAuthMiddlewares, ['tenant', 'tenant.user']);

$workerAuthMiddlewares = ['auth:sanctum', 'abilities:worker,access'];

return [
    'routes' => [
        'public' => [
            ['version' => 'v2', 'prefix' => 'auth', 'file' => 'auth.php'],
            ['version' => 'v2', 'prefix' => 'enums', 'file' => 'enums.php'],
        ],

        'admin' => [
            ['version' => 'v2', 'prefix' => 'admin/notifications',                       'file' => 'notifications.php',           'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/dashboard',                          'file' => 'dashboard.php',              'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/platform-settings',                  'file' => 'platform-settings.php',      'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/profile',                            'file' => 'profile.php',                'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/services',                           'file' => 'services.php',               'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/companies',                          'file' => 'companies.php',              'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/wallet-coupons',                     'file' => 'wallet-coupons.php',         'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/payments',                           'file' => 'payments.php',              'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/workers',                            'file' => 'workers.php',                'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/tasks',                              'file' => 'tasks.php',                  'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/access-control',                     'file' => 'access-control.php',         'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/access-control',                     'file' => 'access-control.php',         'middlewares' => $adminAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/companies/{company}/brands',         'file' => 'catalog-brands.php',         'middlewares' => $adminManageCompaniesMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/companies/{company}/sub-brands',     'file' => 'catalog-sub-brands.php',     'middlewares' => $adminManageCompaniesMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/companies/{company}/categories',     'file' => 'catalog-categories.php',     'middlewares' => $adminManageCompaniesMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/companies/{company}/sub-categories', 'file' => 'catalog-sub-categories.php', 'middlewares' => $adminManageCompaniesMiddlewares],
            ['version' => 'v2', 'prefix' => 'admin/companies/{company}/products',       'file' => 'catalog-products.php',       'middlewares' => $adminManageCompaniesMiddlewares],
        ],

        'worker' => [
            ['version' => 'v2', 'prefix' => 'worker/notifications', 'file' => 'notifications.php', 'middlewares' => $workerAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'worker/account', 'file' => 'account.php', 'middlewares' => $workerAuthMiddlewares],
            ['version' => 'v2', 'prefix' => 'worker/tasks',   'file' => 'tasks.php',   'middlewares' => $workerAuthMiddlewares],
        ],

        'company' => [
            ['version' => 'v2', 'prefix' => 'company/notifications',   'file' => 'notifications.php',   'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/services',       'file' => 'services.php',       'middlewares' => $companyAuthMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/profile',        'file' => 'profile.php',        'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/brands',         'file' => 'brands.php',         'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/sub-brands',     'file' => 'sub-brands.php',     'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/categories',     'file' => 'categories.php',     'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/sub-categories', 'file' => 'sub-categories.php', 'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/products',       'file' => 'products.php',       'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/wallets',        'file' => 'wallets.php',        'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/tasks',          'file' => 'tasks.php',          'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/reports',        'file' => 'reports.php',        'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/access-control', 'file' => 'access-control.php', 'middlewares' => $companyMiddlewares, ],
            ['version' => 'v2', 'prefix' => 'company/access-control', 'file' => 'access-control.php', 'middlewares' => $companyMiddlewares, ],
        ],
    ],

    'providers' => [
        CachingServiceProvider::class,
        AuthenticationModuleServiceProvider::class,
        AccessControlModuleServiceProvider::class,
        BrandsModuleServiceProvider::class,
        CompaniesModuleServiceProvider::class,
        UserModuleServiceProvider::class,
        SubBrandsModuleServiceProvider::class,
        CategoriesModuleServiceProvider::class,
        SubCategoriesModuleServiceProvider::class,
        ProductsModuleServiceProvider::class,
        PlatformSettingsModuleServiceProvider::class,
        ReportsModuleServiceProvider::class,
        ServicesModuleServiceProvider::class,
        AdminsModuleServiceProvider::class,
        CompaniesWalletsModuleServiceProvider::class,
        WorkersModuleServiceProvider::class,
        WorkersWalletsModuleServiceProvider::class,
        CouponsModuleServiceProvider::class,
        TasksModuleServiceProvider::class,
        CompanyUsersModuleServiceProvider::class,
    ],
];
