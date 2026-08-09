<?php

namespace App\Modules\V1\Reports\Application\Caching;

use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;
use App\Modules\Shared\Application\Caching\Data\CacheTtl;
use App\Modules\Shared\Application\Caching\Services\CacheInvalidationDispatcher;
use Closure;

final readonly class CompanyDashboardCache
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
    public function remember(int $companyId, string $period, Closure $resolver): array
    {
        if (! $this->enabled()) {
            return $resolver();
        }

        return $this->cacheStore->flexible(
            self::key($companyId, $period),
            $this->ttl(),
            $resolver,
        );
    }

    public function forgetAfterCommit(int ...$companyIds): void
    {
        $companyIds = array_values(array_unique(array_filter($companyIds)));

        if ($companyIds === []) {
            return;
        }

        $keys = [];

        foreach ($companyIds as $companyId) {
            foreach (self::PERIODS as $period) {
                foreach ($this->locales() as $locale) {
                    $keys[] = self::key($companyId, $period, $locale);
                }
            }
        }

        $this->invalidationDispatcher->forgetAfterCommit(...$keys);
    }

    public static function key(int $companyId, string $period, ?string $locale = null): string
    {
        return sprintf(
            'v1:reports:company-dashboard:%d:%s:%s',
            $companyId,
            $period,
            $locale ?? app()->getLocale(),
        );
    }

    private function enabled(): bool
    {
        return (bool) config('shelfspot_cache.groups.reports', false);
    }

    private function ttl(): CacheTtl
    {
        return new CacheTtl(
            freshSeconds: (int) config('shelfspot_cache.reports.company_dashboard.fresh_seconds'),
            staleSeconds: (int) config('shelfspot_cache.reports.company_dashboard.stale_seconds'),
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
