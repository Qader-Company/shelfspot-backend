<?php

namespace App\Modules\V1\Stores\Infrastructure\Providers;

use App\Modules\V1\Stores\Domain\Repositories\StoreRepositoryInterface;
use App\Modules\V1\Stores\Infrastructure\Persistence\Repositories\EloquentStoreRepository;
use Illuminate\Support\ServiceProvider;

class StoresModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StoreRepositoryInterface::class, EloquentStoreRepository::class);
    }
}
