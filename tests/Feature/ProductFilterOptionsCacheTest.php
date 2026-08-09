<?php

namespace Tests\Feature;

use App\Modules\Shared\Application\Caching\Services\CacheVersionManager;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\Products\Application\Caching\ProductFilterOptionsCache;
use App\Modules\V1\Products\Application\Services\ProductFilterOptionsService;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ProductFilterOptionsCacheTest extends TestCase
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

    public function test_it_reuses_a_filter_options_response_without_rerunning_queries(): void
    {
        $company = $this->company('Cached company');
        $catalog = $this->catalog($company);
        $this->setCompany($company);
        $service = app(ProductFilterOptionsService::class);
        $filters = ['brand_id' => $catalog['brand']->id];

        DB::enableQueryLog();
        $response = $service->resolve($filters);
        $this->assertSame('Brand', $response['data']['brands'][0]['label']);
        $this->assertNotEmpty(DB::getQueryLog());

        DB::flushQueryLog();
        $service->resolve($filters);

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_filter_and_locale_variants_use_distinct_cache_keys(): void
    {
        $company = $this->company('Variants company');
        $catalog = $this->catalog($company);
        $this->setCompany($company);
        $service = app(ProductFilterOptionsService::class);
        $version = app(CacheVersionManager::class)->current(ProductFilterOptionsCache::versionKey($company->id));
        $baseFilters = ['brand_id' => $catalog['brand']->id];
        $filtered = ['brand_id' => $catalog['brand']->id, 'sub_brand_id' => $catalog['subBrand']->id];

        $service->resolve($baseFilters);
        $service->resolve($filtered);
        app()->setLocale('ar');
        $service->resolve($baseFilters);

        $this->assertTrue(Cache::store('array')->has(
            ProductFilterOptionsCache::key($company->id, $version, $baseFilters, 'en')
        ));
        $this->assertTrue(Cache::store('array')->has(
            ProductFilterOptionsCache::key($company->id, $version, $filtered, 'en')
        ));
        $this->assertTrue(Cache::store('array')->has(
            ProductFilterOptionsCache::key($company->id, $version, $baseFilters, 'ar')
        ));
    }

    public function test_catalog_change_advances_only_the_affected_company_revision(): void
    {
        $companyA = $this->company('Company A');
        $companyB = $this->company('Company B');
        $catalogA = $this->catalog($companyA);
        $catalogB = $this->catalog($companyB);
        $service = app(ProductFilterOptionsService::class);

        $this->setCompany($companyA);
        $filtersA = ['brand_id' => $catalogA['brand']->id];
        $service->resolve($filtersA);
        $versionA = app(CacheVersionManager::class)->current(ProductFilterOptionsCache::versionKey($companyA->id));

        $this->setCompany($companyB);
        $filtersB = ['brand_id' => $catalogB['brand']->id];
        $service->resolve($filtersB);
        $versionB = app(CacheVersionManager::class)->current(ProductFilterOptionsCache::versionKey($companyB->id));

        $this->setCompany($companyA);
        $catalogA['brand']->translateOrNew('en')->name = 'Updated brand';
        $catalogA['brand']->save();

        $this->assertSame($versionA + 1, app(CacheVersionManager::class)->current(ProductFilterOptionsCache::versionKey($companyA->id)));
        $this->assertSame($versionB, app(CacheVersionManager::class)->current(ProductFilterOptionsCache::versionKey($companyB->id)));
        $this->assertSame('Updated brand', $service->resolve($filtersA)['data']['brands'][0]['label']);
    }

    public function test_rolled_back_catalog_change_does_not_advance_the_revision(): void
    {
        $company = $this->company('Rollback company');
        $catalog = $this->catalog($company);
        $this->setCompany($company);
        $service = app(ProductFilterOptionsService::class);
        $filters = ['brand_id' => $catalog['brand']->id];

        $service->resolve($filters);
        $versionKey = ProductFilterOptionsCache::versionKey($company->id);
        $version = app(CacheVersionManager::class)->current($versionKey);

        try {
            DB::transaction(function () use ($catalog): void {
                $catalog['brand']->translateOrNew('en')->name = 'Rolled back brand';
                $catalog['brand']->save();

                throw new RuntimeException('Rollback the catalog update.');
            });
        } catch (RuntimeException) {
            // The transaction is intentionally rolled back.
        }

        $this->assertSame($version, app(CacheVersionManager::class)->current($versionKey));
        $this->assertSame('Brand', $service->resolve($filters)['data']['brands'][0]['label']);
    }

    private function setCompany(Company $company): void
    {
        app(TenantContextInterface::class)->setCompany((string) $company->id);
    }

    private function company(string $name): Company
    {
        return Company::query()->create([
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->bothify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{brand: Brand, subBrand: SubBrand, category: Category, subCategory: SubCategory}
     */
    private function catalog(Company $company): array
    {
        $brand = Brand::query()->create(['company_id' => $company->id, 'is_active' => true]);
        $brand->translateOrNew('en')->name = 'Brand';
        $brand->translateOrNew('ar')->name = 'علامة';
        $brand->save();

        $subBrand = SubBrand::query()->create([
            'company_id' => $company->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);
        $subBrand->translateOrNew('en')->name = 'Sub brand';
        $subBrand->translateOrNew('ar')->name = 'علامة فرعية';
        $subBrand->save();

        $category = Category::query()->create([
            'company_id' => $company->id,
            'brand_id' => $brand->id,
            'sub_brand_id' => $subBrand->id,
            'is_active' => true,
        ]);
        $category->translateOrNew('en')->name = 'Category';
        $category->translateOrNew('ar')->name = 'فئة';
        $category->save();

        $subCategory = SubCategory::query()->create([
            'company_id' => $company->id,
            'brand_id' => $brand->id,
            'sub_brand_id' => $subBrand->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $subCategory->translateOrNew('en')->name = 'Sub category';
        $subCategory->translateOrNew('ar')->name = 'فئة فرعية';
        $subCategory->save();

        return compact('brand', 'subBrand', 'category', 'subCategory');
    }
}
