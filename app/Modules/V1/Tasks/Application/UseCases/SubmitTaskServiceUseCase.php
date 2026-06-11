<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceSubmission;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmitTaskServiceUseCase
{
    public function execute(Task $task, TaskService $taskService, Worker $worker, array $formData, array $filesByField = []): TaskServiceSubmission
    {
        return DB::transaction(function () use ($task, $taskService, $worker, $formData, $filesByField) {
            /** @var Task $lockedTask */
            $lockedTask = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            /** @var TaskService $lockedTaskService */
            $lockedTaskService = TaskService::query()
                ->with(['service', 'products'])
                ->whereKey($taskService->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureWorkerCanSubmit($lockedTask, $lockedTaskService, $worker);
            $this->validateSubmission($lockedTaskService, $formData, $filesByField);

            /** @var TaskServiceSubmission $submission */
            $submission = TaskServiceSubmission::query()->updateOrCreate(
                ['task_service_id' => $lockedTaskService->id],
                [
                    'worker_id' => $worker->id,
                    'form_data' => $formData,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]
            );

            $this->syncSubmissionFiles($submission, $filesByField);

            $lockedTaskService->forceFill([
                'status' => TaskServiceStatusEnum::COMPLETED,
            ])->save();

            return $submission->refresh()->load(['taskService.service.translations', 'worker']);
        });
    }

    private function ensureWorkerCanSubmit(Task $task, TaskService $taskService, Worker $worker): void
    {
        if ((int) $taskService->task_id !== (int) $task->id) {
            throw ValidationException::withMessages(['task_service' => __('tasks.validation.service_not_in_task')]);
        }

        if ($task->status !== TaskStatusEnum::IN_PROGRESS) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.submit_in_progress_only')]);
        }

        if ((int) $task->assigned_worker_id !== (int) $worker->id) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.worker_not_assigned')]);
        }
    }

    private function validateSubmission(TaskService $taskService, array $formData, array $filesByField): void
    {
        $service = $taskService->service;
        $fields = $service?->key?->submissionForm()['fields'] ?? [];
        $rules = [];

        foreach ($fields as $field => $definition) {
            $type = (string) ($definition['type'] ?? 'string');

            if (str_contains($type, 'file')) {
                $this->validateFileField($field, $definition, $filesByField);
                continue;
            }

            $rules[$field] = $this->rulesForField($definition);

            if ($type === 'array' && isset($definition['item_fields'])) {
                foreach ($definition['item_fields'] as $itemField => $itemDefinition) {
                    $rules["$field.*.$itemField"] = $this->rulesForField($itemDefinition);
                }
            }
        }

        $validator = Validator::make($formData, $rules);

        $validator->after(function ($validator) use ($taskService, $formData, $fields) {
            $this->validateSubmittedProductsBelongToTaskService($validator, $taskService, $formData, $fields);
        });

        $validator->validate();
    }

    private function rulesForField(array $definition): array
    {
        $rules = [($definition['required'] ?? false) ? 'required' : 'nullable'];
        $type = (string) ($definition['type'] ?? 'string');

        match ($type) {
            'array' => $rules[] = 'array',
            'integer' => $rules[] = 'integer',
            'numeric' => $rules[] = 'numeric',
            'boolean' => $rules[] = 'boolean',
            default => $rules[] = 'string',
        };

        if ($type === 'enum' && isset($definition['values'])) {
            $rules = [($definition['required'] ?? false) ? 'required' : 'nullable', Rule::in($definition['values'])];
        }

        if (isset($definition['max']) && in_array($type, ['string', 'array'], true)) {
            $rules[] = 'max:'.$definition['max'];
        }

        if (isset($definition['min_items']) && $type === 'array') {
            $rules[] = 'min:'.$definition['min_items'];
        }

        return $rules;
    }

    private function validateFileField(string $field, array $definition, array $filesByField): void
    {
        if (! ($definition['required'] ?? false)) {
            return;
        }

        $files = array_filter(Arr::wrap($filesByField[$field] ?? null));

        if (count($files) < (int) ($definition['min_items'] ?? 1)) {
            throw ValidationException::withMessages(["submission_files.$field" => __('tasks.validation.required_file')]);
        }
    }

    private function validateSubmittedProductsBelongToTaskService($validator, TaskService $taskService, array $formData, array $fields): void
    {
        $allowedProductIds = $taskService->products
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($fields as $field => $definition) {
            if (($definition['type'] ?? null) !== 'array' || ! isset($definition['item_fields']['product_id'])) {
                continue;
            }

            foreach ($formData[$field] ?? [] as $index => $item) {
                $productId = isset($item['product_id']) ? (int) $item['product_id'] : null;

                if ($productId !== null && ! in_array($productId, $allowedProductIds, true)) {
                    $validator->errors()->add("$field.$index.product_id", __('tasks.validation.submitted_product_not_in_task_service'));
                }
            }
        }
    }

    private function syncSubmissionFiles(TaskServiceSubmission $submission, array $filesByField): void
    {
        foreach ($filesByField as $field => $files) {
            $submission->clearMediaCollection($field);

            foreach (Arr::wrap($files) as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $submission
                    ->addMedia($file)
                    ->withCustomProperties(['field' => $field])
                    ->toMediaCollection($field);
            }
        }
    }
}
