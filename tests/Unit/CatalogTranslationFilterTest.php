<?php

namespace Tests\Unit;

use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogTranslationFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createCatalogTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('products');
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

    public function test_catalog_name_filters_search_all_translations(): void
    {
        $this->assertTranslationFilterMatches(Brand::class, 'brands', 'brand_translations', 'brand_id');
        $this->assertTranslationFilterMatches(SubBrand::class, 'sub_brands', 'sub_brand_translations', 'sub_brand_id');
        $this->assertTranslationFilterMatches(Category::class, 'categories', 'category_translations', 'category_id');
        $this->assertTranslationFilterMatches(SubCategory::class, 'sub_categories', 'sub_category_translations', 'sub_category_id');
    }

    public function test_product_search_matches_translated_name_as_well_as_sku_and_barcode(): void
    {
        $product = Product::create(['company_id' => 1, 'sku' => 'SKU-123', 'barcode' => 'BAR-456']);
        DB::table('product_translations')->insert([
            'product_id' => $product->id,
            'locale' => 'ar',
            'name' => 'عصير برتقال',
            'description' => null,
        ]);

        $this->assertSame([$product->id], Product::filter(['search' => 'برتقال'])->pluck('id')->all());
        $this->assertSame([$product->id], Product::filter(['search' => 'SKU-123'])->pluck('id')->all());
        $this->assertSame([$product->id], Product::filter(['search' => 'BAR-456'])->pluck('id')->all());
    }

    private function assertTranslationFilterMatches(string $modelClass, string $table, string $translationTable, string $foreignKey): void
    {
        $model = $modelClass::create(['company_id' => 1]);
        DB::table($translationTable)->insert([
            $foreignKey => $model->id,
            'locale' => 'ar',
            'name' => 'قهوة عربية',
        ]);

        $this->assertSame([$model->id], $modelClass::filter(['name' => 'عربية'])->pluck('id')->all(), $table);
    }

    private function createCatalogTables(): void
    {
        $this->createCatalogTable('brands');
        $this->createTranslationTable('brand_translations', 'brand_id');
        $this->createCatalogTable('sub_brands');
        $this->createTranslationTable('sub_brand_translations', 'sub_brand_id');
        $this->createCatalogTable('categories');
        $this->createTranslationTable('category_translations', 'category_id');
        $this->createCatalogTable('sub_categories');
        $this->createTranslationTable('sub_category_translations', 'sub_category_id');
        $this->createCatalogTable('products', true);
        $this->createTranslationTable('product_translations', 'product_id', true);
    }

    private function createCatalogTable(string $table, bool $isProduct = false): void
    {
        Schema::create($table, function (Blueprint $tableBlueprint) use ($isProduct): void {
            $tableBlueprint->id();
            $tableBlueprint->unsignedBigInteger('company_id');
            $tableBlueprint->boolean('is_active')->default(true);

            if ($isProduct) {
                $tableBlueprint->string('sku')->nullable();
                $tableBlueprint->string('barcode')->nullable();
            }

            $tableBlueprint->timestamps();
            $tableBlueprint->softDeletes();
        });
    }

    private function createTranslationTable(string $table, string $foreignKey, bool $hasDescription = false): void
    {
        Schema::create($table, function (Blueprint $tableBlueprint) use ($foreignKey, $hasDescription): void {
            $tableBlueprint->id();
            $tableBlueprint->unsignedBigInteger($foreignKey);
            $tableBlueprint->string('locale', 2);
            $tableBlueprint->string('name');

            if ($hasDescription) {
                $tableBlueprint->text('description')->nullable();
            }
        });
    }
}
