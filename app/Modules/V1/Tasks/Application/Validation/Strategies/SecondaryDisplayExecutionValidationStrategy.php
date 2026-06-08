<?php

namespace App\Modules\V1\Tasks\Application\Validation\Strategies;

class SecondaryDisplayExecutionValidationStrategy extends AbstractTaskServiceValidationStrategy
{
    protected function fileFields(): array
    {
        return [
            'planogram_files' => ['required' => true, 'min_items' => 1],
            'job_order_files' => ['required' => false, 'min_items' => 1],
        ];
    }
}
