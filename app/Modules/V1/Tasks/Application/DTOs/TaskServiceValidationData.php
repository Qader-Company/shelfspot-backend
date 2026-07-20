<?php

namespace App\Modules\V1\Tasks\Application\DTOs;

use App\Modules\V1\Services\Domain\Models\Service;
use Illuminate\Support\Arr;

readonly class TaskServiceValidationData
{
    public function __construct(
        public int $index,
        public array $taskService,
        public Service $service,
        public array $filesByField,
        public array $existingFilesByField = [],
    ) {
    }

    public function products(): array
    {
        return $this->taskService['products'] ?? [];
    }

    public function allFilesByField(): array
    {
        return collect(array_unique([
            ...array_keys($this->existingFilesByField),
            ...array_keys($this->filesByField),
        ]))
            ->mapWithKeys(fn (string $field) => [
                $field => [
                    ...Arr::wrap($this->existingFilesByField[$field] ?? []),
                    ...Arr::wrap($this->filesByField[$field] ?? []),
                ],
            ])
            ->all();
    }
}
