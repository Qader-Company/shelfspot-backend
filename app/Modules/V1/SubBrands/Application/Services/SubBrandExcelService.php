<?php

namespace App\Modules\V1\SubBrands\Application\Services;

use App\Modules\Shared\Application\Excel\AbstractCatalogExcelService;
use App\Modules\V1\SubBrands\Application\Excel\SubBrandExport;
use App\Modules\V1\SubBrands\Application\Excel\SubBrandImport;
use App\Modules\V1\SubBrands\Application\Excel\SubBrandTemplateExport;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\Brands\Domain\Models\Brand;

class SubBrandExcelService extends AbstractCatalogExcelService
{
    protected function config(): array
    {
        return [
            'module' => 'sub-brands',
            'model' => SubBrand::class,
            'filename' => 'sub-brands',
            'sheet' => 'Sub Brands',
            'headings' => ['name_en', 'name_ar', 'brand', 'is_active'],
            'sample' => ['Example Sub Brand', 'مثال علامة فرعية', null, 'yes'],
            'fillable' => ['name_en', 'name_ar', 'brand_id', 'is_active'],
            'required' => ['name', 'brand_id'],
            'parents' => [
                    'brand' => ['model' => Brand::class, 'attribute' => 'brand_id', 'required' => true],
                ],
            'relations' => ['brand'],
        ];
    }

    protected function templateExportClass(): string
    {
        return SubBrandTemplateExport::class;
    }

    protected function exportClass(): string
    {
        return SubBrandExport::class;
    }

    protected function importClass(): string
    {
        return SubBrandImport::class;
    }
}
