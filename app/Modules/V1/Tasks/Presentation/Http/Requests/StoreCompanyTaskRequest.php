<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Tasks\Application\Validation\TaskServiceValidationGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreCompanyTaskRequest extends FormRequest
{
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

                    return $service;
                })
                ->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'execution_time' => ['required', 'date_format:H:i'],
            'location' => ['required', 'array'],
            'location.latitude' => ['required', 'numeric', 'between:-90,90'],
            'location.longitude' => ['required', 'numeric', 'between:-180,180'],
            'location.location_name' => ['nullable', 'string', 'max:255'],
            'location.address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_key' => ['required', 'string', 'distinct', new Enum(ServiceTypeEnum::class)],
            'services.*.price' => ['required', 'numeric', 'min:0'],
            'services.*.execution_time_minutes' => ['required', 'integer', 'min:1'],
            'services.*.execution_instructions' => ['nullable', 'string', 'max:5000'],
            'services.*.products' => ['required', 'array', 'min:1'],
            'services.*.products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'services.*.products.*.product_details' => ['nullable', 'array'],
            'services.*.request_files' => ['nullable', 'array'],
            'services.*.request_files.*' => ['nullable', 'array'],
            'services.*.request_files.*.*' => ['file', 'max:10240'],
        ];

    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateServices($validator);
            $this->validateProducts($validator);
        });
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        $validated['services'] = collect($validated['services'] ?? [])
            ->map(function (array $service, int $index) {
                $service['service_id'] = $this->input("services.$index.service_id");

                return $service;
            })
            ->all();

        return $validated;
    }

    private function validateServices(Validator $validator): void
    {
        $servicesById = Service::query()
            ->whereIn('id', collect($this->input('services', []))->pluck('service_id')->filter())
            ->get()
            ->keyBy('id');

        foreach ($this->input('services', []) as $index => $taskService) {
            $service = $servicesById->get((int) ($taskService['service_id'] ?? 0));

            if (! $service || ! $service->is_active) {
                $validator->errors()->add("services.$index.service_key", __('api.not_found'));
                continue;
            }

            if ((float) $taskService['price'] < (float) $service->minimum_price) {
                $validator->errors()->add("services.$index.price", __('tasks.validation.minimum_price', ['price' => $service->minimum_price]));
            }

            if ((int) $taskService['execution_time_minutes'] < (int) $service->minimum_execution_time) {
                $validator->errors()->add("services.$index.execution_time_minutes", __('tasks.validation.minimum_execution_time', ['minutes' => $service->minimum_execution_time]));
            }

            app(TaskServiceValidationGenerator::class)->validate(
                $index,
                $taskService,
                $service,
                Arr::get($this->allFiles(), "services.$index.request_files", []),
                $validator,
            );
        }
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
