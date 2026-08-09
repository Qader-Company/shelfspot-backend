<?php

namespace App\Modules\Shared\Infrastructure\Caching\Providers;

use App\Modules\Shared\Application\Caching\Contracts\AfterCommitDispatcherInterface;
use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;
use App\Modules\Shared\Application\Caching\Services\CacheInvalidationDispatcher;
use App\Modules\Shared\Application\Caching\Services\CacheVersionManager;
use App\Modules\Shared\Infrastructure\Caching\LaravelAfterCommitDispatcher;
use App\Modules\Shared\Infrastructure\Caching\LaravelCacheStore;
use Illuminate\Support\ServiceProvider;

class CachingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheStoreInterface::class, LaravelCacheStore::class);
        $this->app->singleton(AfterCommitDispatcherInterface::class, LaravelAfterCommitDispatcher::class);
        $this->app->singleton(CacheInvalidationDispatcher::class);
        $this->app->singleton(CacheVersionManager::class);
    }
}
