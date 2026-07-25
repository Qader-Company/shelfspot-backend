<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Tasks\Application\Validation\TaskServiceValidationGenerator;
use App\Modules\V1\Tasks\Domain\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreCompanyTaskRequest extends FormRequest
{
    private const ALLOWED_UPLOAD_MIMES = 'jpg,jpeg,png,webp,pdf';

    private const MAX_UPLOAD_KB = 10240;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $services = $this->input('services');

        if (! is_array($services)) {
            return;
        }

        $serviceKeys = collect($services)
            ->filter(fn ($service) => is_array($service))
            ->pluck('service_key')
            ->filter(fn ($key) => is_string($key) || is_numeric($key))
            ->map(fn ($key) => (string) $key)
            ->unique()
            ->values();

        $servicesByKey = Service::query()
            ->whereIn('key', $serviceKeys)
            ->get()
            ->keyBy(fn (Service $service) => $service->key->value);

        $this->merge([
            'services' => collect($services)
                ->map(function ($service) use ($servicesByKey) {
                    if (! is_array($service)) {
                        return $service;
                    }

                    unset($service['service_id']);

                    $serviceKey = isset($service['service_key']) ? (string) $service['service_key'] : null;
                    $resolvedService = $serviceKey !== null ? $servicesByKey->get($serviceKey) : null;

                    if ($resolvedService !== null) {
                        $service['service_id'] = $resolvedService->id;
                    }

                    unset($service['price']);
                    unset($service['execution_time_minutes']);

                    return $service;
                })
                ->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:tomorrow'],
            'execution_time' => ['prohibited'],
            'location' => ['required', 'array'],
            'location.latitude' => ['required', 'numeric', 'between:-90,90'],
            'location.longitude' => ['required', 'numeric', 'between:-180,180'],
            'location.location_name' => ['nullable', 'string', 'max:255'],
            'location.address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'repeat_task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_key' => ['required', 'string', 'distinct', new Enum(ServiceTypeEnum::class)],
            'services.*.service_id' => ['required', 'integer', 'distinct'],
            'services.*.execution_instructions' => ['nullable', 'string', 'max:5000'],
            'services.*.products' => ['required', 'array', 'min:1'],
            'services.*.products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'services.*.products.*.product_details' => ['nullable', 'array'],
            'services.*.keep_attachment_ids' => ['nullable', 'array'],
            'services.*.keep_attachment_ids.*' => ['integer', 'distinct'],
            'services.*.request_files' => ['nullable', 'array'],
            'services.*.request_files.*' => ['nullable', 'array'],
            'services.*.request_files.*.*' => ['file', 'mimes:'.self::ALLOWED_UPLOAD_MIMES, 'max:'.self::MAX_UPLOAD_KB],
        ];

    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateRepeatTask($validator);
            $this->validateRepeatedAttachments($validator);
            $this->validateServices($validator);
            $this->validateProducts($validator);
        });
    }

    private function validateServices(Validator $validator): void
    {
        $servicesByKey = Service::query()
            ->whereIn('key', collect($this->input('services', []))->pluck('service_key')->filter())
            ->get()
            ->keyBy('key');
        foreach ($this->input('services', []) as $index => $taskService) {
            $service = $servicesByKey->get(($taskService['service_key'] ?? 0));
            if (! $service || ! $service->is_active) {
                $validator->errors()->add("services.$index.service_key", __('api.not_found'));

                continue;
            }

            app(TaskServiceValidationGenerator::class)->validate(
                $index,
                $taskService,
                $service,
                Arr::get($this->allFiles(), "services.$index.request_files", []),
                $validator,
                $this->existingRepeatedFilesByField($taskService),
            );
        }
    }

    private function validateRepeatTask(Validator $validator): void
    {
        if ($this->input('repeat_task_id') !== null && $this->repeatTask() === null) {
            $validator->errors()->add('repeat_task_id', __('api.not_found'));
        }
    }

    private function validateRepeatedAttachments(Validator $validator): void
    {
        $attachmentIds = collect($this->input('services', []))
            ->flatMap(fn (array $service) => $service['keep_attachment_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($attachmentIds->isEmpty()) {
            return;
        }

        $repeatTask = $this->repeatTask();

        if ($repeatTask === null) {
            foreach ($this->input('services', []) as $serviceIndex => $taskService) {
                if (! empty($taskService['keep_attachment_ids'] ?? [])) {
                    $validator->errors()->add("services.$serviceIndex.keep_attachment_ids", __('validation.prohibited', ['attribute' => 'keep_attachment_ids']));
                }
            }

            return;
        }

        $mediaById = $repeatTask->services
            ->flatMap(fn ($taskService) => $taskService->media)
            ->keyBy('id');

        foreach ($this->input('services', []) as $serviceIndex => $taskService) {
            $serviceId = (int) ($taskService['service_id'] ?? 0);

            foreach ($taskService['keep_attachment_ids'] ?? [] as $attachmentId) {
                $media = $mediaById->get((int) $attachmentId);

                if ($media === null || (int) $media->model?->service_id !== $serviceId) {
                    $validator->errors()->add("services.$serviceIndex.keep_attachment_ids", __('api.not_found'));
                }
            }
        }
    }

    private function existingRepeatedFilesByField(array $taskService): array
    {
        $repeatTask = $this->repeatTask();

        if ($repeatTask === null) {
            return [];
        }

        $keepAttachmentIds = collect($taskService['keep_attachment_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($keepAttachmentIds === []) {
            return [];
        }

        return $repeatTask->services
            ->firstWhere('service_id', (int) ($taskService['service_id'] ?? 0))
            ?->media
            ->whereIn('id', $keepAttachmentIds)
            ->groupBy(fn ($media) => $media->getCustomProperty('field'))
            ->map(fn ($media) => $media->all())
            ->all() ?? [];
    }

    private function repeatTask(): ?Task
    {
        $repeatTaskId = $this->input('repeat_task_id');

        if ($repeatTaskId === null) {
            return null;
        }

        $companyId = app(TenantContextInterface::class)->getCompanyId();

        return Task::query()
            ->with('services.media')
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->find((int) $repeatTaskId);
    }

    private function validateProducts(Validator $validator): void
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();
        $productIds = collect($this->input('services', []))
            ->flatMap(fn (array $service) => collect($service['products'] ?? [])->pluck('product_id'))
            ->unique()
            ->values();

        $validProductIds = Product::withoutGlobalScopes()
            ->whereIn('id', $productIds)
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($this->input('services', []) as $serviceIndex => $taskService) {
            foreach ($taskService['products'] ?? [] as $productIndex => $product) {
                if (! in_array((int) $product['product_id'], $validProductIds, true)) {
                    $validator->errors()->add("services.$serviceIndex.products.$productIndex.product_id", __('tasks.validation.product_not_in_company'));
                }
            }
        }
    }
}
