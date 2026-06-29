<?php

namespace Tests\Unit;

use App\Modules\Shared\Application\Excel\AbstractCatalogImport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CatalogImportEmptyRowTest extends TestCase
{
    public function test_row_with_only_status_column_is_treated_as_empty(): void
    {
        $import = new class ([
            'headings' => ['name', 'brand', 'is_active'],
        ]) extends AbstractCatalogImport {
        };

        $reflection = new ReflectionClass($import);
        $isEmptyRow = $reflection->getMethod('isEmptyRow');

        $this->assertTrue($isEmptyRow->invoke($import, [
            'name' => null,
            'brand' => null,
            'is_active' => 'yes',
        ]));
    }

    public function test_row_with_business_data_is_not_treated_as_empty(): void
    {
        $import = new class ([
            'headings' => ['name', 'brand', 'is_active'],
        ]) extends AbstractCatalogImport {
        };

        $reflection = new ReflectionClass($import);
        $isEmptyRow = $reflection->getMethod('isEmptyRow');

        $this->assertFalse($isEmptyRow->invoke($import, [
            'name' => 'Example Sub Brand',
            'brand' => null,
            'is_active' => 'yes',
        ]));
    }
}
