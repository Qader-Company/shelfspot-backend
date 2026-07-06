<?php

namespace App\Modules\Shared\Application\Excel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

abstract class AbstractCatalogExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly array $config)
    {
    }

    public function collection(): Collection
    {
        return $this->config['model']::query()
            ->with($this->config['relations'])
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return $this->config['headings'];
    }

    public function map($row): array
    {
        return array_map(fn (string $heading) => $this->value($row, $heading), $this->headings());
    }

    public function title(): string
    {
        return $this->config['sheet'];
    }

    private function value(Model $row, string $heading): mixed
    {
        return match ($heading) {
            'brand' => $this->optionLabel($row->brand),
            'sub_brand' => $this->optionLabel($row->subBrand),
            'category' => $this->optionLabel($row->category),
            'sub_category' => $this->optionLabel($row->subCategory),
            'is_active' => $row->is_active ? 'yes' : 'no',
            'name_en' => $row->translate('en')?->name,
            'name_ar' => $row->translate('ar')?->name,
            'description_en' => $row->translate('en')?->description,
            'description_ar' => $row->translate('ar')?->description,
            default => $row->{$heading},
        };
    }

    private function optionLabel(?Model $model): ?string
    {
        if (! $model) {
            return null;
        }

        return $model->getKey().' - '.$model->name;
    }
}
