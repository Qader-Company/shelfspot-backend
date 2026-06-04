<?php

namespace App\Modules\V1\Products\Application\Services;

use App\Modules\Shared\Application\Excel\AbstractCatalogExcelService;
use App\Modules\V1\Products\Application\Excel\ProductExport;
use App\Modules\V1\Products\Application\Excel\ProductImport;
use App\Modules\V1\Products\Application\Excel\ProductTemplateExport;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;

class ProductExcelService extends AbstractCatalogExcelService
{
    protected function config(): array
    {
        return [
            'module' => 'products',
            'model' => Product::class,
            'filename' => 'products',
            'sheet' => 'Products',
            'headings' => ['name', 'brand', 'sub_brand', 'category', 'sub_category', 'sku', 'description', 'is_active'],
            'sample' => ['Example Product', null, null, null, null, 'SKU-001', 'Optional description', 'yes'],
            'fillable' => ['name', 'brand_id', 'sub_brand_id', 'category_id', 'sub_category_id', 'sku', 'description', 'is_active'],
            'required' => ['name'],
            'parents' => [
                    'brand' => ['model' => Brand::class, 'attribute' => 'brand_id', 'required' => false],
                    'sub_brand' => ['model' => SubBrand::class, 'attribute' => 'sub_brand_id', 'required' => false],
                    'category' => ['model' => Category::class, 'attribute' => 'category_id', 'required' => false],
                    'sub_category' => ['model' => SubCategory::class, 'attribute' => 'sub_category_id', 'required' => false],
                ],
            'relations' => ['brand', 'subBrand', 'category', 'subCategory'],
        ];
    }

    protected function templateExportClass(): string
    {
        return ProductTemplateExport::class;
    }

    protected function exportClass(): string
    {
        return ProductExport::class;
    }

    protected function importClass(): string
    {
        return ProductImport::class;
    }
}
