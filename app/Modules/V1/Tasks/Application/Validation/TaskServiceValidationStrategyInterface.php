<?php

namespace App\Modules\V1\Tasks\Application\Validation;

use Illuminate\Validation\Validator;

interface TaskServiceValidationStrategyInterface
{
    public function validate(TaskServiceValidationData $data, Validator $validator): void;
}
