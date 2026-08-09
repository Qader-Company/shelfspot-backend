<?php

namespace App\Modules\V1\Services\Infrastructure\Providers;

use App\Modules\V1\Services\Domain\Repositories\ServiceRepositoryInterface;
use App\Modules\V1\Services\Infrastructure\Listeners\ServiceCatalogCacheInvalidator;
use App\Modules\V1\Services\Infrastructure\Persistence\Repositories\EloquentServiceRepository;
use Illuminate\Support\ServiceProvider;

class ServicesModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ServiceRepositoryInterface::class, EloquentServiceRepository::class);
    }

    public function boot(ServiceCatalogCacheInvalidator $serviceCatalogCacheInvalidator): void
    {
        $serviceCatalogCacheInvalidator->register();
    }
}
