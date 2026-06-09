<?php

namespace App\Modules\V1\Tasks\Application\Validation\Strategies;

class FreshnessReportValidationStrategy extends AbstractTaskServiceValidationStrategy
{
    protected function productDetailsRules(): array
    {
        return [
            'expected_quantity' => ['nullable', 'integer', 'min:1'],
            'expected_expiry_date' => ['nullable', 'date'],
        ];
    }
}
