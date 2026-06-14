<?php

namespace App\Modules\V1\Tasks\Application\Validation\Strategies;

use App\Modules\V1\Tasks\Application\DTOs\TaskServiceValidationData;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Validator;

abstract class AbstractTaskServiceValidationStrategy
{
    public function validate(TaskServiceValidationData $data, Validator $validator): void
    {
        $this->validateMisplacedProductDetails($data, $validator);
        $this->validateProductDetails($data, $validator);
        $this->validateRequiredFiles($data, $validator);
    }

    protected function productDetailsRules(): array
    {
        return [];
    }

    protected function allowedProductDetailsFields(): array
    {
        return array_keys($this->productDetailsRules());
    }

    protected function fileFields(TaskServiceValidationData $data): array
    {
        return collect($data->service->key->requestForm()['fields'] ?? [])
            ->filter(fn (array $definition) => str_contains((string) ($definition['type'] ?? ''), 'file'))
            ->map(fn (array $definition) => [
                'required' => (bool) ($definition['required'] ?? false),
                'min_items' => (int) ($definition['min_items'] ?? 1),
            ])
            ->all();
    }

    private function validateMisplacedProductDetails(TaskServiceValidationData $data, Validator $validator): void
    {
        foreach (array_keys($this->productDetailsRules()) as $field) {
            if (array_key_exists($field, $data->taskService['request_details'] ?? [])) {
                $validator->errors()->add(
                    "services.{$data->index}.request_details.$field",
                    __('validation.prohibited', ['attribute' => $field]),
                );
            }
        }
    }

    private function validateProductDetails(TaskServiceValidationData $data, Validator $validator): void
    {
        $rules = $this->productDetailsRules();

        foreach ($data->products() as $productIndex => $product) {
            $productDetails = $product['product_details'] ?? [];
            $unexpectedFields = array_diff(array_keys($productDetails), $this->allowedProductDetailsFields());

            foreach ($unexpectedFields as $field) {
                $validator->errors()->add(
                    "services.{$data->index}.products.$productIndex.product_details.$field",
                    __('validation.prohibited', ['attribute' => $field]),
                );
            }

            if ($rules === []) {
                continue;
            }

            $detailsValidator = ValidatorFactory::make($productDetails, $rules);

            foreach ($detailsValidator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add("services.{$data->index}.products.$productIndex.product_details.$field", $message);
                }
            }
        }
    }

    private function validateRequiredFiles(TaskServiceValidationData $data, Validator $validator): void
    {
        foreach ($this->fileFields($data) as $field => $definition) {
            if (! ($definition['required'] ?? false)) {
                continue;
            }

            $files = array_filter(Arr::wrap(Arr::get($data->filesByField, $field)));

            if (count($files) < ($definition['min_items'] ?? 1)) {
                $validator->errors()->add("services.{$data->index}.request_files.$field", __('tasks.validation.required_file'));
            }
        }
    }
}
