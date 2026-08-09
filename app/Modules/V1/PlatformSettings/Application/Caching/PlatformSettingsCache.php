<?php

namespace App\Modules\V1\PlatformSettings\Application\Caching;

use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;
use App\Modules\Shared\Application\Caching\Services\CacheInvalidationDispatcher;
use Closure;

final readonly class PlatformSettingsCache
{
    public function __construct(
        private CacheStoreInterface $cacheStore,
        private CacheInvalidationDispatcher $invalidationDispatcher,
    ) {}

    /**
     * @param  Closure(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    public function remember(Closure $resolver): array
    {
        if (! $this->enabled()) {
            return $resolver();
        }

        return $this->cacheStore->remember(
            self::key(),
            (int) config('shelfspot_cache.reference_data.platform_settings_seconds'),
            $resolver,
        );
    }

    public function forgetAfterCommit(): void
    {
        $this->invalidationDispatcher->forgetAfterCommit(...array_map(
            self::key(...),
            $this->locales(),
        ));
    }

    public static function key(?string $locale = null): string
    {
        return 'v1:platform-settings:locale-'.($locale ?? app()->getLocale());
    }

    private function enabled(): bool
    {
        return (bool) config('shelfspot_cache.groups.platform_settings', false);
    }

    /**
     * @return array<int, string>
     */
    private function locales(): array
    {
        return config('shelfspot_cache.locales', [config('app.locale')]);
    }
}
