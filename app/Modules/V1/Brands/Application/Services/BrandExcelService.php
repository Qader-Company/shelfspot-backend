<?php

namespace App\Modules\V1\Brands\Application\Services;

use App\Modules\Shared\Application\Excel\AbstractCatalogExcelService;
use App\Modules\V1\Brands\Application\Excel\BrandExport;
use App\Modules\V1\Brands\Application\Excel\BrandImport;
use App\Modules\V1\Brands\Application\Excel\BrandTemplateExport;
use App\Modules\V1\Brands\Domain\Models\Brand;

class BrandExcelService extends AbstractCatalogExcelService
{
    protected function config(): array
    {
        return [
            'module' => 'brands',
            'model' => Brand::class,
            'filename' => 'brands',
            'sheet' => 'Brands',
            'headings' => ['name_en', 'name_ar', 'is_active'],
            'sample' => ['Example Brand', 'مثال علامة تجارية', 'yes'],
            'fillable' => ['name_en', 'name_ar', 'is_active'],
            'required' => ['name'],
            'parents' => [],
            'relations' => [],
        ];
    }

    protected function templateExportClass(): string
    {
        return BrandTemplateExport::class;
    }

    protected function exportClass(): string
    {
        return BrandExport::class;
    }

    protected function importClass(): string
    {
        return BrandImport::class;
    }
}
