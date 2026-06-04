<?php

namespace App\Modules\V1\Categories\Application\Services;

use App\Modules\Shared\Application\Excel\AbstractCatalogExcelService;
use App\Modules\V1\Categories\Application\Excel\CategoryExport;
use App\Modules\V1\Categories\Application\Excel\CategoryImport;
use App\Modules\V1\Categories\Application\Excel\CategoryTemplateExport;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;

class CategoryExcelService extends AbstractCatalogExcelService
{
    protected function config(): array
    {
        return [
            'module' => 'categories',
            'model' => Category::class,
            'filename' => 'categories',
            'sheet' => 'Categories',
            'headings' => ['name', 'brand', 'sub_brand', 'is_active'],
            'sample' => ['Example Category', null, null, 'yes'],
            'fillable' => ['name', 'brand_id', 'sub_brand_id', 'is_active'],
            'required' => ['name'],
            'parents' => [
                    'brand' => ['model' => Brand::class, 'attribute' => 'brand_id', 'required' => false],
                    'sub_brand' => ['model' => SubBrand::class, 'attribute' => 'sub_brand_id', 'required' => false],
                ],
            'relations' => ['brand', 'subBrand'],
        ];
    }

    protected function templateExportClass(): string
    {
        return CategoryTemplateExport::class;
    }

    protected function exportClass(): string
    {
        return CategoryExport::class;
    }

    protected function importClass(): string
    {
        return CategoryImport::class;
    }
}
