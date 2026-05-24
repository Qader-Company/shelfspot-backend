<?php

return [
    'providers' => [
        \App\Modules\V1\Brands\Infrastructure\Providers\BrandsModuleServiceProvider::class,
        \App\Modules\V1\Companies\Infrastructure\Providers\CompanyModuleServiceProvider::class,
        \App\Modules\V1\Users\Infrastructure\Providers\UserModuleServiceProvider::class,
        \App\Modules\V1\SubBrands\Infrastructure\Providers\SubBrandsModuleServiceProvider::class,
    ],
];
