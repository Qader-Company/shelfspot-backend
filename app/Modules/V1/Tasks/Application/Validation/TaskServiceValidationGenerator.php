<?php

namespace App\Modules\V1\Tasks\Application\Validation;

use App\Modules\V1\Services\Domain\Models\Service;
use Illuminate\Validation\Validator;

class TaskServiceValidationGenerator
{
    public function __construct(
        private readonly TaskServiceValidationStrategyFactory $strategyFactory,
    ) {
    }

    public function validate(int $index, array $taskService, Service $service, array $filesByField, Validator $validator): void
    {
        $this->strategyFactory
            ->make($service->key)
            ->validate(
                new TaskServiceValidationData($index, $taskService, $service, $filesByField),
                $validator,
            );
    }
}
