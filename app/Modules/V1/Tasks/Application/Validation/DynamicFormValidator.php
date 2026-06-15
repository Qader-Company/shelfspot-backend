<?php

namespace App\Modules\V1\Tasks\Application\Validation;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DynamicFormValidator
{
    public function validate(array $fields, array $data, array $filesByField = [], string $fileErrorPrefix = 'files'): void
    {
        $this->validateFiles($fields, $filesByField, $fileErrorPrefix);

        Validator::make($data, $this->rulesForFields($fields))->validate();
    }

    public function rulesForFields(array $fields): array
    {
        $rules = [];

        foreach ($fields as $field => $definition) {
            $type = (string) ($definition['type'] ?? 'string');

            if ($this->isFileField($definition)) {
                continue;
            }

            $rules[$field] = $this->rulesForField($definition);

            if ($type === 'array' && isset($definition['item_fields'])) {
                foreach ($definition['item_fields'] as $itemField => $itemDefinition) {
                    $rules["$field.*.$itemField"] = $this->rulesForField($itemDefinition);
                }
            }
        }

        return $rules;
    }

    public function rulesForField(array $definition): array
    {
        $requiredRule = ($definition['required'] ?? false) ? 'required' : 'nullable';
        $type = (string) ($definition['type'] ?? 'string');

        $rules = match ($type) {
            'array' => [$requiredRule, 'array'],
            'integer' => [$requiredRule, 'integer'],
            'numeric' => [$requiredRule, 'numeric'],
            'boolean' => [$requiredRule, 'boolean'],
            'date' => [$requiredRule, 'date'],
            'enum' => [$requiredRule, Rule::in($definition['values'] ?? [])],
            default => [$requiredRule, 'string'],
        };

        if (isset($definition['max']) && in_array($type, ['string', 'array', 'integer', 'numeric'], true)) {
            $rules[] = 'max:'.$definition['max'];
        }

        if (isset($definition['min']) && in_array($type, ['string', 'integer', 'numeric'], true)) {
            $rules[] = 'min:'.$definition['min'];
        }

        if (isset($definition['min_items']) && $type === 'array') {
            $rules[] = 'min:'.$definition['min_items'];
        }

        if (isset($definition['max_items']) && $type === 'array') {
            $rules[] = 'max:'.$definition['max_items'];
        }

        return $rules;
    }

    public function validateFiles(array $fields, array $filesByField, string $errorPrefix = 'files'): void
    {
        foreach ($fields as $field => $definition) {
            if (! $this->isFileField($definition) || ! ($definition['required'] ?? false)) {
                continue;
            }

            $files = array_filter(Arr::wrap(Arr::get($filesByField, $field)));

            if (count($files) < (int) ($definition['min_items'] ?? 1)) {
                throw ValidationException::withMessages(["$errorPrefix.$field" => __('tasks.validation.required_file')]);
            }
        }
    }

    private function isFileField(array $definition): bool
    {
        return str_contains((string) ($definition['type'] ?? ''), 'file');
    }
}
