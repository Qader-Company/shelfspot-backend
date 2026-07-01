<?php

namespace App\Modules\Shared\Application\Excel;

class CatalogExcelResult
{
    public function __construct(
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $skipped = 0,
        public readonly array $errors = [],
        public readonly int $totalRows = 0,
    ) {
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function toArray(): array
    {
        $failedRows = $this->failedRows();
        $successfulRows = $this->created + $this->updated;

        return [
            'status' => $this->status(),
            'import_mode' => 'partial_success',
            'has_errors' => $this->hasErrors(),
            'total_rows' => $this->totalRows,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'processed_rows' => $successfulRows + $failedRows,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }

    private function status(): string
    {
        if (! $this->hasErrors()) {
            return 'completed';
        }

        if (($this->created + $this->updated) > 0) {
            return 'completed_with_errors';
        }

        return 'failed';
    }

    private function failedRows(): int
    {
        return collect($this->errors)
            ->pluck('row')
            ->filter(fn ($row): bool => $row > 0)
            ->unique()
            ->count();
    }
}
