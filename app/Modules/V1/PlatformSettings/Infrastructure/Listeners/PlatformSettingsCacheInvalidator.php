<?php

namespace App\Modules\V1\PlatformSettings\Infrastructure\Listeners;

use App\Modules\V1\PlatformSettings\Application\Caching\PlatformSettingsCache;
use App\Modules\V1\PlatformSettings\Domain\Models\PlatformSetting;

final readonly class PlatformSettingsCacheInvalidator
{
    public function __construct(private PlatformSettingsCache $cache) {}

    public function register(): void
    {
        PlatformSetting::saved(fn () => $this->cache->forgetAfterCommit());
        PlatformSetting::deleted(fn () => $this->cache->forgetAfterCommit());
    }
}
