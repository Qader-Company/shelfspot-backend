<?php

namespace App\Modules\V1\Services\Infrastructure\Listeners;

use App\Modules\V1\Services\Application\Caching\ServiceCatalogCache;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\Models\ServiceTranslation;

final readonly class ServiceCatalogCacheInvalidator
{
    public function __construct(private ServiceCatalogCache $cache) {}

    public function register(): void
    {
        Service::saved(fn () => $this->cache->incrementVersionAfterCommit());
        Service::deleted(fn () => $this->cache->incrementVersionAfterCommit());
        ServiceTranslation::saved(fn () => $this->cache->incrementVersionAfterCommit());
        ServiceTranslation::deleted(fn () => $this->cache->incrementVersionAfterCommit());
    }
}
