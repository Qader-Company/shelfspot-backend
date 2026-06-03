<?php

namespace App\Modules\Shared\Application\Excel;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class AbstractCatalogExcelService
{
    abstract protected function config(): array;

    abstract protected function templateExportClass(): string;

    abstract protected function exportClass(): string;

    abstract protected function importClass(): string;

    public function template(): BinaryFileResponse
    {
        $config = $this->config();
        $templateClass = $this->templateExportClass();

        return Excel::download(
            new $templateClass($config, $this->options($config)),
            $config['filename'].'-template.xlsx'
        );
    }

    public function export(): BinaryFileResponse
    {
        $config = $this->config();
        $exportClass = $this->exportClass();

        return Excel::download(
            new $exportClass($config),
            $config['filename'].'-export.xlsx'
        );
    }

    public function import(UploadedFile $file): CatalogExcelResult
    {
        $config = $this->config();
        $importClass = $this->importClass();
        $import = new $importClass($config);

        Excel::import($import, $file);

        return $import->result();
    }

    private function options(array $config): array
    {
        $options = [];

        foreach ($config['parents'] as $heading => $parent) {
            $options[$heading] = $parent['model']::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Model $model): string => $model->getKey().' - '.$model->getAttribute('name'))
                ->all();
        }

        return $options;
    }
}
