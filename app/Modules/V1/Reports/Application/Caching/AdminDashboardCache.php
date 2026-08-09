<?php

namespace App\Modules\V1\Reports\Application\Caching;

use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;
use App\Modules\Shared\Application\Caching\Data\CacheTtl;
use App\Modules\Shared\Application\Caching\Services\CacheInvalidationDispatcher;
use Closure;

final readonly class AdminDashboardCache
{
    private const PERIODS = ['week', 'month', 'year'];

    public function __construct(
        private CacheStoreInterface $cacheStore,
        private CacheInvalidationDispatcher $invalidationDispatcher,
    ) {}

    /**
     * @param  Closure(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    public function remember(string $period, Closure $resolver): array
    {
        if (! $this->enabled()) {
            return $resolver();
        }

        return $this->cacheStore->flexible(
            self::key($period),
            $this->ttl(),
            $resolver,
        );
    }

    public function forgetAfterCommit(): void
    {
        $this->invalidationDispatcher->forgetAfterCommit(...$this->keys());
    }

    public static function key(string $period, ?string $locale = null): string
    {
        return sprintf(
            'v1:reports:admin-dashboard:%s:%s',
            $period,
            $locale ?? app()->getLocale(),
        );
    }

    /**
     * @return array<int, string>
     */
    private function keys(): array
    {
        $keys = [];

        foreach (self::PERIODS as $period) {
            foreach ($this->locales() as $locale) {
                $keys[] = self::key($period, $locale);
            }
        }

        return $keys;
    }

    private function enabled(): bool
    {
        return (bool) config('shelfspot_cache.groups.reports', false);
    }

    private function ttl(): CacheTtl
    {
        return new CacheTtl(
            freshSeconds: (int) config('shelfspot_cache.reports.admin_dashboard.fresh_seconds'),
            staleSeconds: (int) config('shelfspot_cache.reports.admin_dashboard.stale_seconds'),
        );
    }

    /**
     * @return array<int, string>
     */
    private function locales(): array
    {
        return config('shelfspot_cache.locales', [config('app.locale')]);
    }
}
