<?php

namespace Tests\Unit;

use App\Modules\V1\Products\Application\Services\ProductFilterOptionsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductFilterOptionsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createCatalogTables();
        app()->setLocale('en');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sub_category_translations');
        Schema::dropIfExists('sub_categories');
        Schema::dropIfExists('category_translations');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('sub_brand_translations');
        Schema::dropIfExists('sub_brands');
        Schema::dropIfExists('brand_translations');
        Schema::dropIfExists('brands');

        parent::tearDown();
    }

    public function test_it_returns_translated_options_without_selecting_name_from_catalog_tables(): void
    {
        $brandId = $this->insertCatalogRecord('brands');
        $this->insertTranslation('brand_translations', 'brand_id', $brandId, 'Acme');

        $zuluSubBrandId = $this->insertCatalogRecord('sub_brands', ['brand_id' => $brandId]);
        $this->insertTranslation('sub_brand_translations', 'sub_brand_id', $zuluSubBrandId, 'Zulu');
        $alphaSubBrandId = $this->insertCatalogRecord('sub_brands', ['brand_id' => $brandId]);
        $this->insertTranslation('sub_brand_translations', 'sub_brand_id', $alphaSubBrandId, 'Alpha');

        $categoryId = $this->insertCatalogRecord('categories', ['brand_id' => $brandId]);
        $this->insertTranslation('category_translations', 'category_id', $categoryId, 'Drinks');
        $subCategoryId = $this->insertCatalogRecord('sub_categories', [
            'brand_id' => $brandId,
            'category_id' => $categoryId,
        ]);
        $this->insertTranslation('sub_category_translations', 'sub_category_id', $subCategoryId, 'Juice');

        $result = app(ProductFilterOptionsService::class)->resolve(['brand_id' => $brandId]);

        $this->assertSame(['Acme'], collect($result['data']['brands'])->pluck('label')->all());
        $this->assertSame(['Alpha', 'Zulu'], collect($result['data']['sub_brands'])->pluck('label')->all());
        $this->assertSame(['Drinks'], collect($result['data']['categories'])->pluck('label')->all());
        $this->assertSame(['Juice'], collect($result['data']['sub_categories'])->pluck('label')->all());
    }

    private function createCatalogTables(): void
    {
        $this->createCatalogTable('brands');
        $this->createTranslationTable('brand_translations', 'brand_id');
        $this->createCatalogTable('sub_brands', true);
        $this->createTranslationTable('sub_brand_translations', 'sub_brand_id');
        $this->createCatalogTable('categories', true, true);
        $this->createTranslationTable('category_translations', 'category_id');
        $this->createCatalogTable('sub_categories', true, true, true);
        $this->createTranslationTable('sub_category_translations', 'sub_category_id');
    }

    private function createCatalogTable(string $table, bool $hasBrand = false, bool $hasSubBrand = false, bool $hasCategory = false): void
    {
        Schema::create($table, function (Blueprint $tableBlueprint) use ($hasBrand, $hasSubBrand, $hasCategory): void {
            $tableBlueprint->id();
            $tableBlueprint->unsignedBigInteger('company_id');

            if ($hasBrand) {
                $tableBlueprint->unsignedBigInteger('brand_id')->nullable();
            }

            if ($hasSubBrand) {
                $tableBlueprint->unsignedBigInteger('sub_brand_id')->nullable();
            }

            if ($hasCategory) {
                $tableBlueprint->unsignedBigInteger('category_id')->nullable();
            }

            $tableBlueprint->boolean('is_active')->default(true);
            $tableBlueprint->timestamps();
            $tableBlueprint->softDeletes();
        });
    }

    private function createTranslationTable(string $table, string $foreignKey): void
    {
        Schema::create($table, function (Blueprint $tableBlueprint) use ($foreignKey): void {
            $tableBlueprint->id();
            $tableBlueprint->unsignedBigInteger($foreignKey);
            $tableBlueprint->string('locale', 2);
            $tableBlueprint->string('name');
        });
    }

    private function insertCatalogRecord(string $table, array $attributes = []): int
    {
        return DB::table($table)->insertGetId([
            'company_id' => 1,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function insertTranslation(string $table, string $foreignKey, int $id, string $name): void
    {
        DB::table($table)->insert([
            $foreignKey => $id,
            'locale' => 'en',
            'name' => $name,
        ]);
    }
}
