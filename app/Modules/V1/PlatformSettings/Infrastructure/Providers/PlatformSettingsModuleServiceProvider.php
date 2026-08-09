<?php

namespace App\Modules\V1\PlatformSettings\Infrastructure\Providers;

use App\Modules\V1\PlatformSettings\Infrastructure\Listeners\PlatformSettingsCacheInvalidator;
use Illuminate\Support\ServiceProvider;

class PlatformSettingsModuleServiceProvider extends ServiceProvider
{
    public function boot(PlatformSettingsCacheInvalidator $platformSettingsCacheInvalidator): void
    {
        $platformSettingsCacheInvalidator->register();
    }
}
