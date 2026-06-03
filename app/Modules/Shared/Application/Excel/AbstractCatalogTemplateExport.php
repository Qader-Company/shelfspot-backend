<?php

namespace App\Modules\Shared\Application\Excel;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractCatalogTemplateExport implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly array $config,
        private readonly array $options,
    ) {
    }

    public function array(): array
    {
        return [$this->config['sample']];
    }

    public function headings(): array
    {
        return $this->config['headings'];
    }

    public function title(): string
    {
        return $this->config['sheet'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $highestColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
                $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$highestColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEAF2F8');
                $sheet->setAutoFilter("A1:{$highestColumn}1");

                $this->addOptionsSheet($sheet);
                $this->addValidations($sheet);
            },
        ];
    }

    private function addOptionsSheet(Worksheet $sheet): void
    {
        $spreadsheet = $sheet->getParent();
        $optionsSheet = new Worksheet($spreadsheet, 'Options');
        $spreadsheet->addSheet($optionsSheet);

        $column = 1;
        foreach ($this->options as $key => $values) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $optionsSheet->setCellValue("{$letter}1", $key);

            foreach (array_values($values) as $index => $value) {
                $optionsSheet->setCellValue($letter.($index + 2), $value);
            }

            $column++;
        }

        $optionsSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    }

    private function addValidations(Worksheet $sheet): void
    {
        foreach ($this->config['parents'] as $heading => $_parent) {
            $this->applyListValidation($sheet, $heading, $heading);
        }

        $this->applyInlineValidation($sheet, 'is_active', ['yes', 'no']);
    }

    private function applyListValidation(Worksheet $sheet, string $heading, string $optionKey): void
    {
        $headings = $this->headings();
        $columnIndex = array_search($heading, $headings, true);

        if ($columnIndex === false || ! isset($this->options[$optionKey]) || $this->options[$optionKey] === []) {
            return;
        }

        $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
        $optionColumnLetter = Coordinate::stringFromColumnIndex(array_search($optionKey, array_keys($this->options), true) + 1);
        $lastOptionRow = count($this->options[$optionKey]) + 1;
        $formula = "'Options'!\${$optionColumnLetter}\$2:\${$optionColumnLetter}\${$lastOptionRow}";

        $this->applyValidation($sheet, $columnLetter, $formula, false);
    }

    private function applyInlineValidation(Worksheet $sheet, string $heading, array $values): void
    {
        $columnIndex = array_search($heading, $this->headings(), true);

        if ($columnIndex === false) {
            return;
        }

        $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
        $formula = '"'.implode(',', $values).'"';
        $this->applyValidation($sheet, $columnLetter, $formula, true);
    }

    private function applyValidation(Worksheet $sheet, string $columnLetter, string $formula, bool $inline): void
    {
        for ($row = 2; $row <= 1001; $row++) {
            $validation = $sheet->getCell("{$columnLetter}{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Invalid value');
            $validation->setError('Please select a value from the dropdown list.');
            $validation->setPromptTitle('Select value');
            $validation->setPrompt('Choose one of the available values for this company.');
            $validation->setFormula1($inline ? $formula : '='.$formula);
        }
    }
}
