<?php

namespace App\Modules\V1\Brands\Infrastructure\Providers;

use App\Modules\V1\Brands\Domain\Repositories\{BrandRepositoryInterface};
use App\Modules\V1\Brands\Infrastructure\Persistence\Repositories\{EloquentBrandRepository};
use Illuminate\Support\ServiceProvider;

class BrandsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BrandRepositoryInterface::class, EloquentBrandRepository::class);
    }
}