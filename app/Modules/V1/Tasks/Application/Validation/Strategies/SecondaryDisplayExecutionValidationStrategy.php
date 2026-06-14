<?php

namespace App\Modules\V1\Tasks\Application\Validation\Strategies;

use App\Modules\V1\Tasks\Application\DTOs\TaskServiceValidationData;

class SecondaryDisplayExecutionValidationStrategy extends AbstractTaskServiceValidationStrategy
{
    protected function fileFields(TaskServiceValidationData $data): array
    {
        return [
            'planogram_files' => ['required' => true, 'min_items' => 1],
            'job_order_files' => ['required' => false, 'min_items' => 1],
        ];
    }
}
