<?php

    $adminAuthMiddlewares = ['auth:sanctum', 'abilities:admin,access'];
    $adminManageCompaniesMiddlewares = $adminAuthMiddlewares + ['tenant.route-company'];

    $companyAuthMiddlewares = ['auth:sanctum', 'abilities:company,access'];
    $companyMiddlewares = $companyAuthMiddlewares + ['tenant', 'tenant.user'];

    $workerAuthMiddlewares = ['auth:sanctum', 'abilities:worker,access'];

return [
    "routes" => [
        'public' => [
            ['prefix' => 'auth', 'file' => 'auth.php'],
            ['prefix' => 'enums', 'file' => 'enums.php'],
        ],

        'admin' => [
            [
                'prefix' => 'admin/services',
                'file' => 'services.php',
                'middlewares' => $adminAuthMiddlewares],
            [
                'prefix' => 'admin/companies',
                'file' => 'companies.php',
                'middlewares' => $adminAuthMiddlewares],
            [
                'prefix' => 'admin/wallet-coupons',
                'file' => 'wallet-coupons.php',
                'middlewares' => $adminAuthMiddlewares],
            [
                'prefix' => 'admin/workers',
                'file' => 'workers.php',
                'middlewares' => $adminAuthMiddlewares],
            [
                'prefix' => 'admin/tasks',
                'file' => 'tasks.php',
                'middlewares' => $adminAuthMiddlewares],
            [
                'prefix' => 'admin/access-control',
                'file' => 'access-control.php',
                'middlewares' => $adminAuthMiddlewares],
            [
                'prefix' => 'admin/companies/{company}/brands',
                'file' => 'catalog-brands.php',
                'middlewares' => $adminManageCompaniesMiddlewares],
            [
                'prefix' => 'admin/companies/{company}/sub-brands',
                'file' => 'catalog-sub-brands.php',
                'middlewares' => $adminManageCompaniesMiddlewares],
            [
                'prefix' => 'admin/companies/{company}/categories',
                'file' => 'catalog-categories.php',
                'middlewares' => $adminManageCompaniesMiddlewares],
            [
                'prefix' => 'admin/companies/{company}/sub-categories',
                'file' => 'catalog-sub-categories.php',
                'middlewares' => $adminManageCompaniesMiddlewares],
            [
                'prefix' => 'admin/companies/{company}/products',
                'file' => 'catalog-products.php',
                'middlewares' => $adminManageCompaniesMiddlewares],
        ],

        'worker' => [
            [
                'prefix' => 'worker/account',
                'file' => 'account.php',
                'middlewares' => $workerAuthMiddlewares
            ],
            [
                'prefix' => 'worker/tasks',
                'file' => 'tasks.php',
                'middlewares' => $workerAuthMiddlewares
            ],
        ],

        'company' => [
            [
                'prefix' => 'company/services',
                'file' => 'services.php',
                'middlewares' => $companyAuthMiddlewares
            ],
            [
                'prefix' => 'company/brands',
                'file' => 'brands.php',
                'middlewares' => $companyMiddlewares
            ],
            [
                'prefix' => 'company/sub-brands',
                'file' => 'sub-brands.php',
                'middlewares' => $companyMiddlewares
            ],
            [
                'prefix' => 'company/categories',
                'file' => 'categories.php',
                'middlewares' => $companyMiddlewares
            ],
            [
                'prefix' => 'company/sub-categories',
                'file' => 'sub-categories.php',
                'middlewares' => $companyMiddlewares
            ],
            [
                'prefix' => 'company/products',
                'file' => 'products.php',
                'middlewares' => $companyMiddlewares
            ],
            [
                'prefix' => 'company/wallets',
                'file' => 'wallets.php',
                'middlewares' => $companyMiddlewares
            ],
            [
                'prefix' => 'company/tasks',
                'file' => 'tasks.php',
                'middlewares' => $companyMiddlewares
            ],
            [
                'prefix' => 'company/access-control',
                'file' => 'access-control.php',
                'middlewares' => $companyMiddlewares
            ],
        ]
    ],

    'providers' => [
        \App\Modules\V1\AccessControl\Infrastructure\Providers\AccessControlModuleServiceProvider::class,
        \App\Modules\V1\Brands\Infrastructure\Providers\BrandsModuleServiceProvider::class,
        \App\Modules\V1\Companies\Infrastructure\Providers\CompaniesModuleServiceProvider::class,
        \App\Modules\V1\Users\Infrastructure\Providers\UserModuleServiceProvider::class,
        \App\Modules\V1\SubBrands\Infrastructure\Providers\SubBrandsModuleServiceProvider::class,
        \App\Modules\V1\Categories\Infrastructure\Providers\CategoriesModuleServiceProvider::class,
        \App\Modules\V1\SubCategories\Infrastructure\Providers\SubCategoriesModuleServiceProvider::class,
        \App\Modules\V1\Products\Infrastructure\Providers\ProductsModuleServiceProvider::class,
        \App\Modules\V1\Services\Infrastructure\Providers\ServicesModuleServiceProvider::class,
        \App\Modules\V1\Admins\Infrastructure\Providers\AdminsModuleServiceProvider::class,
        \App\Modules\V1\CompaniesWallets\Infrastructure\Providers\CompaniesWalletsModuleServiceProvider::class,
        \App\Modules\V1\Workers\Infrastructure\Providers\WorkersModuleServiceProvider::class,
        \App\Modules\V1\WorkersWallets\Infrastructure\Providers\WorkersWalletsModuleServiceProvider::class,
        \App\Modules\V1\Coupons\Infrastructure\Providers\CouponsModuleServiceProvider::class,
        \App\Modules\V1\Tasks\Infrastructure\Providers\TasksModuleServiceProvider::class,
    ],
];
