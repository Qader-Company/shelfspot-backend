<?php

namespace App\Modules\V1\Tasks\Application\Validation;

use App\Modules\V1\Services\Domain\Models\Service;

readonly class TaskServiceValidationData
{
    public function __construct(
        public int $index,
        public array $taskService,
        public Service $service,
        public array $filesByField,
    ) {
    }

    public function requestDetails(): array
    {
        return $this->taskService['request_details'] ?? [];
    }
}
