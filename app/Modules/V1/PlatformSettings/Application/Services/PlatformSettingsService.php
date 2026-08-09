<?php

namespace App\Modules\V1\PlatformSettings\Application\Services;

use App\Modules\V1\PlatformSettings\Application\Caching\PlatformSettingsCache;
use App\Modules\V1\PlatformSettings\Application\Data\PlatformSettingsData;
use App\Modules\V1\PlatformSettings\Domain\Models\PlatformSetting;

final readonly class PlatformSettingsService
{
    public function __construct(private PlatformSettingsCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        return $this->cache->remember(
            fn (): array => PlatformSettingsData::from(PlatformSetting::query()->firstOrCreate()),
        );
    }
}
