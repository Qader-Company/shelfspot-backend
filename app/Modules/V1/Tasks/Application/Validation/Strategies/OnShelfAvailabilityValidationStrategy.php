<?php

namespace App\Modules\V1\Tasks\Application\Validation\Strategies;

class OnShelfAvailabilityValidationStrategy extends AbstractTaskServiceValidationStrategy
{
    protected function productDetailsRules(): array
    {
        return [
            'minimum_quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
