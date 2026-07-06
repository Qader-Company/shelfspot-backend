<?php

namespace App\Modules\V1\SubCategories\Application\Services;

use App\Modules\Shared\Application\Excel\AbstractCatalogExcelService;
use App\Modules\V1\SubCategories\Application\Excel\SubCategoryExport;
use App\Modules\V1\SubCategories\Application\Excel\SubCategoryImport;
use App\Modules\V1\SubCategories\Application\Excel\SubCategoryTemplateExport;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\Categories\Domain\Models\Category;

class SubCategoryExcelService extends AbstractCatalogExcelService
{
    protected function config(): array
    {
        return [
            'module' => 'sub-categories',
            'model' => SubCategory::class,
            'filename' => 'sub-categories',
            'sheet' => 'Sub Categories',
            'headings' => ['name_en', 'name_ar', 'brand', 'sub_brand', 'category', 'is_active'],
            'sample' => ['Example Sub Category', 'مثال تصنيف فرعي', null, null, null, 'yes'],
            'fillable' => ['name_en', 'name_ar', 'brand_id', 'sub_brand_id', 'category_id', 'is_active'],
            'required' => ['name', 'category_id'],
            'parents' => [
                    'brand' => ['model' => Brand::class, 'attribute' => 'brand_id', 'required' => false],
                    'sub_brand' => ['model' => SubBrand::class, 'attribute' => 'sub_brand_id', 'required' => false],
                    'category' => ['model' => Category::class, 'attribute' => 'category_id', 'required' => true],
                ],
            'relations' => ['brand', 'subBrand', 'category'],
        ];
    }

    protected function templateExportClass(): string
    {
        return SubCategoryTemplateExport::class;
    }

    protected function exportClass(): string
    {
        return SubCategoryExport::class;
    }

    protected function importClass(): string
    {
        return SubCategoryImport::class;
    }
}
