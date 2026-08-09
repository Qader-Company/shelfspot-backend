<?php

namespace App\Modules\V1\Services\Application\Caching;

use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;
use App\Modules\Shared\Application\Caching\Services\CacheVersionManager;
use Closure;

final readonly class ServiceCatalogCache
{
    public function __construct(
        private CacheStoreInterface $cacheStore,
        private CacheVersionManager $versionManager,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  Closure(): array<int, array<string, mixed>>  $resolver
     * @return array<int, array<string, mixed>>
     */
    public function remember(array $filters, Closure $resolver): array
    {
        if (! $this->enabled()) {
            return $resolver();
        }

        $version = $this->versionManager->current(self::versionKey());

        return $this->cacheStore->remember(
            self::key($version, $filters),
            (int) config('shelfspot_cache.reference_data.services_seconds'),
            $resolver,
        );
    }

    public function incrementVersionAfterCommit(): void
    {
        $this->versionManager->incrementAfterCommit(self::versionKey());
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function key(int $version, array $filters, ?string $locale = null): string
    {
        return sprintf(
            'v1:services:catalog:revision-%d:locale-%s:active-%s',
            $version,
            $locale ?? app()->getLocale(),
            self::activeSegment($filters),
        );
    }

    public static function versionKey(): string
    {
        return 'v1:services:catalog:version';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function activeSegment(array $filters): string
    {
        if (! array_key_exists('active', $filters)) {
            return 'all';
        }

        return filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    private function enabled(): bool
    {
        return (bool) config('shelfspot_cache.groups.catalog', false);
    }
}
