<?php

return [
    'providers' => [
        \App\Modules\V1\Brands\Infrastructure\Providers\BrandsModuleServiceProvider::class,
        \App\Modules\V1\Companies\Infrastructure\Providers\CompanyModuleServiceProvider::class,
        \App\Modules\V1\Users\Infrastructure\Providers\UserModuleServiceProvider::class,
        \App\Modules\V1\SubBrands\Infrastructure\Providers\SubBrandsModuleServiceProvider::class,
        \App\Modules\V1\Categories\Infrastructure\Providers\CategoriesModuleServiceProvider::class,
        \App\Modules\V1\SubCategories\Infrastructure\Providers\SubCategoriesModuleServiceProvider::class,
        \App\Modules\V1\Products\Infrastructure\Providers\ProductsModuleServiceProvider::class,
    ],
];
