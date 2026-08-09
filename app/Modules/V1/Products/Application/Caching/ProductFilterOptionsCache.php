<?php

namespace App\Modules\V1\Products\Application\Caching;

use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;
use App\Modules\Shared\Application\Caching\Services\CacheVersionManager;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use Closure;

final readonly class ProductFilterOptionsCache
{
    public function __construct(
        private CacheStoreInterface $cacheStore,
        private CacheVersionManager $versionManager,
        private TenantContextInterface $tenantContext,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  Closure(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    public function remember(array $filters, Closure $resolver): array
    {
        if (! $this->enabled() || $this->tenantContext->getCompanyId() === null) {
            return $resolver();
        }

        $companyId = $this->tenantContext->getCompanyId();
        $version = $this->versionManager->current(self::versionKey($companyId));

        return $this->cacheStore->remember(
            self::key($companyId, $version, $filters),
            (int) config('shelfspot_cache.reference_data.product_filter_options_seconds'),
            $resolver,
        );
    }

    public function incrementVersionAfterCommit(int ...$companyIds): void
    {
        $companyIds = array_values(array_unique(array_filter($companyIds)));

        if ($companyIds === []) {
            return;
        }

        $this->versionManager->incrementAfterCommit(
            ...array_map(self::versionKey(...), $companyIds),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function key(int $companyId, int $version, array $filters, ?string $locale = null): string
    {
        return sprintf(
            'v1:products:filter-options:company-%d:revision-%d:locale-%s:brand-%s:sub-brand-%s:category-%s:sub-category-%s',
            $companyId,
            $version,
            $locale ?? app()->getLocale(),
            self::segment($filters['brand_id'] ?? null),
            self::segment($filters['sub_brand_id'] ?? null),
            self::segment($filters['category_id'] ?? null),
            self::segment($filters['sub_category_id'] ?? null),
        );
    }

    public static function versionKey(int $companyId): string
    {
        return "v1:products:filter-options:company-{$companyId}:version";
    }

    private static function segment(mixed $value): string
    {
        return $value === null ? 'none' : (string) (int) $value;
    }

    private function enabled(): bool
    {
        return (bool) config('shelfspot_cache.groups.catalog', false);
    }
}
