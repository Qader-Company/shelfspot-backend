<?php

namespace App\Modules\V1\Tasks\Application\Validation\Strategies;

use App\Modules\V1\Tasks\Application\Validation\TaskServiceValidationData;
use App\Modules\V1\Tasks\Application\Validation\TaskServiceValidationStrategyInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Validator;

abstract class AbstractTaskServiceValidationStrategy implements TaskServiceValidationStrategyInterface
{
    public function validate(TaskServiceValidationData $data, Validator $validator): void
    {
        $this->validateRequestDetails($data, $validator);
        $this->validateRequiredFiles($data, $validator);
    }

    protected function requestDetailsRules(): array
    {
        return [];
    }

    protected function allowedRequestDetailsFields(): array
    {
        return array_keys($this->requestDetailsRules());
    }

    protected function fileFields(): array
    {
        return [
            'planogram_files' => ['required' => true, 'min_items' => 1],
        ];
    }

    private function validateRequestDetails(TaskServiceValidationData $data, Validator $validator): void
    {
        $rules = $this->requestDetailsRules();
        $requestDetails = $data->requestDetails();
        $unexpectedFields = array_diff(array_keys($requestDetails), $this->allowedRequestDetailsFields());

        foreach ($unexpectedFields as $field) {
            $validator->errors()->add("services.{$data->index}.request_details.$field", __('validation.prohibited', ['attribute' => $field]));
        }

        if ($rules === []) {
            return;
        }

        $detailsValidator = ValidatorFactory::make($requestDetails, $rules);

        foreach ($detailsValidator->errors()->messages() as $field => $messages) {
            foreach ($messages as $message) {
                $validator->errors()->add("services.{$data->index}.request_details.$field", $message);
            }
        }
    }

    private function validateRequiredFiles(TaskServiceValidationData $data, Validator $validator): void
    {
        foreach ($this->fileFields() as $field => $definition) {
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
