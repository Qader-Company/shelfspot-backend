<?php

namespace App\Modules\V1\Products\Infrastructure\Providers;

use App\Modules\V1\Products\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\V1\Products\Infrastructure\Listeners\ProductFilterOptionsCacheInvalidator;
use App\Modules\V1\Products\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

class ProductsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
    }

    public function boot(ProductFilterOptionsCacheInvalidator $productFilterOptionsCacheInvalidator): void
    {
        $productFilterOptionsCacheInvalidator->register();
    }
}
