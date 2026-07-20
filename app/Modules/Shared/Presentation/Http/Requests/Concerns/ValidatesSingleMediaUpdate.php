<?php

namespace App\Modules\Shared\Presentation\Http\Requests\Concerns;

use App\Modules\Shared\Domain\ValueObjects\SingleMediaUpdateActionEnum;
use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesSingleMediaUpdate
{
    protected function singleMediaActionRules(string $actionField): array
    {
        return [
            $actionField => ['sometimes', Rule::enum(SingleMediaUpdateActionEnum::class)],
        ];
    }

    protected function validateSingleMediaUpdate(string $fileField, string $actionField): Closure
    {
        return function (Validator $validator) use ($fileField, $actionField): void {
            $action = $this->input($actionField);

            if ($action === SingleMediaUpdateActionEnum::REPLACE->value && ! $this->hasFile($fileField)) {
                $validator->errors()->add($fileField, "The {$fileField} field is required when {$actionField} is replace.");
            }

            if (
                in_array($action, [SingleMediaUpdateActionEnum::KEEP->value, SingleMediaUpdateActionEnum::REMOVE->value], true)
                && $this->hasFile($fileField)
            ) {
                $validator->errors()->add($fileField, "The {$fileField} field must not be sent when {$actionField} is {$action}.");
            }
        };
    }
}
