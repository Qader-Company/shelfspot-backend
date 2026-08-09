<?php

namespace Tests\Feature;

use App\Modules\Shared\Application\Caching\Services\CacheVersionManager;
use App\Modules\V1\Services\Application\Caching\ServiceCatalogCache;
use App\Modules\V1\Services\Application\Services\ServiceCatalogService;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ServiceCatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('shelfspot_cache.enabled', true);
        config()->set('shelfspot_cache.groups.catalog', true);
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

    public function test_it_reuses_a_service_catalog_response_without_rerunning_queries(): void
    {
        $this->service();
        $catalog = app(ServiceCatalogService::class);

        DB::enableQueryLog();
        $services = $catalog->list(['active' => true]);

        $this->assertSame('Initial description', $services[0]['description']);
        $this->assertNotEmpty(DB::getQueryLog());

        DB::flushQueryLog();
        $catalog->list(['active' => true]);

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_active_filter_and_locale_variants_use_distinct_cache_keys(): void
    {
        $this->service();
        $catalog = app(ServiceCatalogService::class);
        $version = app(CacheVersionManager::class)->current(ServiceCatalogCache::versionKey());

        $catalog->list([]);
        $catalog->list(['active' => true]);
        app()->setLocale('ar');
        $catalog->list(['active' => true]);

        $this->assertTrue(Cache::store('array')->has(ServiceCatalogCache::key($version, [], 'en')));
        $this->assertTrue(Cache::store('array')->has(ServiceCatalogCache::key($version, ['active' => true], 'en')));
        $this->assertTrue(Cache::store('array')->has(ServiceCatalogCache::key($version, ['active' => true], 'ar')));
    }

    public function test_a_service_change_advances_the_catalog_revision(): void
    {
        $service = $this->service();
        $catalog = app(ServiceCatalogService::class);
        $catalog->list(['active' => true]);
        $version = app(CacheVersionManager::class)->current(ServiceCatalogCache::versionKey());

        $service->update(['price' => 75]);

        $this->assertSame($version + 1, app(CacheVersionManager::class)->current(ServiceCatalogCache::versionKey()));
        $this->assertSame(75, $catalog->list(['active' => true])[0]['price']);
    }

    public function test_a_rolled_back_service_change_does_not_advance_the_catalog_revision(): void
    {
        $service = $this->service();
        $catalog = app(ServiceCatalogService::class);
        $catalog->list(['active' => true]);
        $version = app(CacheVersionManager::class)->current(ServiceCatalogCache::versionKey());

        try {
            DB::transaction(function () use ($service): void {
                $service->update(['price' => 99]);

                throw new RuntimeException('Rollback the service update.');
            });
        } catch (RuntimeException) {
            // The transaction is intentionally rolled back.
        }

        $this->assertSame($version, app(CacheVersionManager::class)->current(ServiceCatalogCache::versionKey()));
        $this->assertSame(50, $catalog->list(['active' => true])[0]['price']);
    }

    private function service(): Service
    {
        $service = Service::query()->create([
            'key' => ServiceTypeEnum::PRIMARY_DISPLAY,
            'price' => 50,
            'is_active' => true,
        ]);
        $service->translateOrNew('en')->description = 'Initial description';
        $service->translateOrNew('ar')->description = 'وصف أولي';
        $service->save();

        return $service;
    }
}
