<?php

namespace Tests\Feature;

use App\Modules\V1\PlatformSettings\Application\Caching\PlatformSettingsCache;
use App\Modules\V1\PlatformSettings\Application\Services\PlatformSettingsService;
use App\Modules\V1\PlatformSettings\Domain\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class PlatformSettingsCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('shelfspot_cache.enabled', true);
        config()->set('shelfspot_cache.groups.platform_settings', true);
        config()->set('shelfspot_cache.store', 'array');
        Cache::store('array')->flush();
        app()->setLocale('en');
    }

    protected function tearDown(): void
    {
        app()->setLocale(config('app.locale'));
        Cache::store('array')->flush();

        parent::tearDown();
    }

    public function test_it_reuses_the_platform_settings_response_without_rerunning_queries(): void
    {
        PlatformSetting::query()->create(['email' => 'support@example.com']);
        $service = app(PlatformSettingsService::class);

        DB::enableQueryLog();
        $settings = $service->current();

        $this->assertSame('support@example.com', $settings['email']);
        $this->assertNotEmpty(DB::getQueryLog());

        DB::flushQueryLog();
        $service->current();

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_a_platform_settings_update_invalidates_every_locale_variant(): void
    {
        $setting = PlatformSetting::query()->create(['email' => 'old@example.com']);
        $service = app(PlatformSettingsService::class);

        $service->current();
        app()->setLocale('ar');
        $service->current();

        $this->assertTrue(Cache::store('array')->has(PlatformSettingsCache::key('en')));
        $this->assertTrue(Cache::store('array')->has(PlatformSettingsCache::key('ar')));

        $setting->update(['email' => 'new@example.com']);

        $this->assertFalse(Cache::store('array')->has(PlatformSettingsCache::key('en')));
        $this->assertFalse(Cache::store('array')->has(PlatformSettingsCache::key('ar')));
        $this->assertSame('new@example.com', $service->current()['email']);
    }

    public function test_a_rolled_back_update_keeps_the_cached_platform_settings(): void
    {
        $setting = PlatformSetting::query()->create(['email' => 'stable@example.com']);
        $service = app(PlatformSettingsService::class);
        $cacheKey = PlatformSettingsCache::key('en');
        $service->current();

        try {
            DB::transaction(function () use ($setting): void {
                $setting->update(['email' => 'rolled-back@example.com']);

                throw new RuntimeException('Rollback the platform settings update.');
            });
        } catch (RuntimeException) {
            // The transaction is intentionally rolled back.
        }

        $this->assertTrue(Cache::store('array')->has($cacheKey));
        $this->assertSame('stable@example.com', $service->current()['email']);
    }
}
