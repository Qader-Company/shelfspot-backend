<?php

namespace App\Modules\V1\Services\Application\Services;

use App\Modules\V1\Services\Application\Caching\ServiceCatalogCache;
use App\Modules\V1\Services\Application\Data\ServiceCatalogData;
use App\Modules\V1\Services\Domain\Repositories\ServiceRepositoryInterface;

final readonly class ServiceCatalogService
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository,
        private ServiceCatalogCache $cache,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function list(array $filters): array
    {
        return $this->cache->remember(
            $filters,
            fn (): array => $this->serviceRepository
                ->getAll(relations: ['translations'], filters: $filters)
                ->map(fn ($service): array => ServiceCatalogData::from($service))
                ->values()
                ->all(),
        );
    }
}
