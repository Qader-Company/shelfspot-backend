<?php

return [
    "routes" => [
        'public' => [
            ['prefix' => 'auth', 'file' => 'auth.php'],
            ['prefix' => 'enums', 'file' => 'enums.php'],
        ],

        'admin' => [
            ['prefix' => 'admin/services', 'file' => 'services.php', 'middlewares' => ['auth:sanctum']],
            ['prefix' => 'admin/companies', 'file' => 'companies.php', 'middlewares' => ['auth:sanctum']],
            ['prefix' => 'admin/companies/{company}/catalog/brands', 'file' => 'catalog-brands.php', 'middlewares' => ['auth:sanctum', 'tenant.route-company']],
            ['prefix' => 'admin/companies/{company}/catalog/sub-brands', 'file' => 'catalog-sub-brands.php', 'middlewares' => ['auth:sanctum', 'tenant.route-company']],
            ['prefix' => 'admin/companies/{company}/catalog/categories', 'file' => 'catalog-categories.php', 'middlewares' => ['auth:sanctum', 'tenant.route-company']],
            ['prefix' => 'admin/companies/{company}/catalog/sub-categories', 'file' => 'catalog-sub-categories.php', 'middlewares' => ['auth:sanctum', 'tenant.route-company']],
            ['prefix' => 'admin/companies/{company}/catalog/products', 'file' => 'catalog-products.php', 'middlewares' => ['auth:sanctum', 'tenant.route-company']],
        ],

        'worker' => [
//            ['prefix' => '', 'file' => '', 'middlewares' => []],
        ],

        'company' => [
            ['prefix' => 'company/brands', 'file' => 'brands.php', 'middlewares' => ['auth:sanctum', 'tenant']],
            ['prefix' => 'company/sub-brands', 'file' => 'sub-brands.php', 'middlewares' => ['auth:sanctum', 'tenant']],
            ['prefix' => 'company/categories', 'file' => 'categories.php', 'middlewares' => ['auth:sanctum', 'tenant']],
            ['prefix' => 'company/sub-categories', 'file' => 'sub-categories.php', 'middlewares' => ['auth:sanctum', 'tenant']],
            ['prefix' => 'company/products', 'file' => 'products.php', 'middlewares' => ['auth:sanctum', 'tenant']],
            ['prefix' => 'company/services', 'file' => 'services.php', 'middlewares' => ['auth:sanctum']],
        ]
    ],

    'providers' => [
        \App\Modules\V1\Brands\Infrastructure\Providers\BrandsModuleServiceProvider::class,
        \App\Modules\V1\Companies\Infrastructure\Providers\CompanyModuleServiceProvider::class,
        \App\Modules\V1\Users\Infrastructure\Providers\UserModuleServiceProvider::class,
        \App\Modules\V1\SubBrands\Infrastructure\Providers\SubBrandsModuleServiceProvider::class,
        \App\Modules\V1\Categories\Infrastructure\Providers\CategoriesModuleServiceProvider::class,
        \App\Modules\V1\SubCategories\Infrastructure\Providers\SubCategoriesModuleServiceProvider::class,
        \App\Modules\V1\Products\Infrastructure\Providers\ProductsModuleServiceProvider::class,
        \App\Modules\V1\Services\Infrastructure\Providers\ServicesModuleServiceProvider::class,
        \App\Modules\V1\Admins\Infrastructure\Providers\AdminsModuleServiceProvider::class,
    ],
];
