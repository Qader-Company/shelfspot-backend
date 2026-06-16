<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use App\Modules\V1\Tasks\Application\Validation\DynamicFormValidator;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class SubmitTaskServiceRequest extends FormRequest
{
    private const ALLOWED_UPLOAD_MIMES = 'jpg,jpeg,png,webp,pdf';
    private const MAX_UPLOAD_KB = 10240;

    private ?Task $task = null;
    private ?TaskService $taskService = null;
    private ?Worker $worker = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'form_data' => ['nullable', 'array'],
            'submission_files' => ['nullable', 'array'],
            'submission_files.*' => ['nullable', 'array'],
            'submission_files.*.*' => ['file', 'mimes:'.self::ALLOWED_UPLOAD_MIMES, 'max:'.self::MAX_UPLOAD_KB],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $task = $this->taskModel();
            $taskService = $this->taskServiceModel();
            $worker = $this->workerModel();

            if (! $task) {
                $validator->errors()->add('task', __('api.not_found'));

                return;
            }

            if (! $taskService) {
                $validator->errors()->add('task_service', __('api.not_found'));

                return;
            }

            $this->validateWorkerCanSubmit($validator, $task, $taskService, $worker);

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateDynamicSubmission($validator, $taskService);
        });
    }

    public function formData(): array
    {
        return $this->validated('form_data') ?? [];
    }

    public function submissionFiles(): array
    {
        return $this->allFiles()['submission_files'] ?? [];
    }

    public function taskModel(): ?Task
    {
        if ($this->task === null) {
            $this->task = Task::query()->find((int) $this->route('id'));
        }

        return $this->task;
    }

    public function taskServiceModel(): ?TaskService
    {
        if ($this->taskService === null) {
            $this->taskService = TaskService::query()
                ->with(['service.translations', 'products'])
                ->find((int) $this->route('serviceId'));
        }

        return $this->taskService;
    }

    public function workerModel(): ?Worker
    {
        if ($this->worker === null) {
            $this->worker = $this->user()?->worker;
        }

        return $this->worker;
    }

    private function validateWorkerCanSubmit(Validator $validator, Task $task, TaskService $taskService, ?Worker $worker): void
    {
        if ((int) $taskService->task_id !== (int) $task->id) {
            $validator->errors()->add('task_service', __('tasks.validation.service_not_in_task'));
        }

        if ($task->status !== TaskStatusEnum::IN_PROGRESS) {
            $validator->errors()->add('task', __('tasks.validation.submit_in_progress_only'));
        }

        if (! $worker || (int) $task->assigned_worker_id !== (int) $worker->id) {
            $validator->errors()->add('task', __('tasks.validation.worker_not_assigned'));
        }
    }

    private function validateDynamicSubmission(Validator $validator, TaskService $taskService): void
    {
        $fields = $taskService->service?->key?->submissionForm()['fields'] ?? [];
        $dynamicFormValidator = app(DynamicFormValidator::class);

        try {
            $dynamicFormValidator->validateFiles($fields, $this->submissionFiles(), 'submission_files');
        } catch (ValidationException $exception) {
            $this->copyValidationErrors($validator, $exception->errors());
        }

        $formDataValidator = ValidatorFactory::make(
            $this->formDataInput(),
            $dynamicFormValidator->rulesForFields($fields),
        );
        $formDataValidator->passes();

        $this->validateSubmittedProductsBelongToTaskService($formDataValidator, $taskService, $fields);
        $this->copyValidationErrors($validator, $formDataValidator->errors()->messages(), 'form_data');
    }

    private function validateSubmittedProductsBelongToTaskService(Validator $validator, TaskService $taskService, array $fields): void
    {
        $allowedProductIds = $taskService->products
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($fields as $field => $definition) {
            if (($definition['type'] ?? null) !== 'array' || ! isset($definition['item_fields']['product_id'])) {
                continue;
            }

            foreach ($this->formDataInput()[$field] ?? [] as $index => $item) {
                $productId = isset($item['product_id']) ? (int) $item['product_id'] : null;

                if ($productId !== null && ! in_array($productId, $allowedProductIds, true)) {
                    $validator->errors()->add("$field.$index.product_id", __('tasks.validation.submitted_product_not_in_task_service'));
                }
            }
        }
    }

    private function copyValidationErrors(Validator $validator, array $errors, ?string $prefix = null): void
    {
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $validator->errors()->add($prefix ? "$prefix.$field" : $field, $message);
            }
        }
    }

    private function formDataInput(): array
    {
        $formData = $this->input('form_data', []);

        return is_array($formData) ? $formData : [];
    }
}
