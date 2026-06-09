<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Tasks\Application\Validation\TaskServiceValidationGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class StoreCompanyTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'services.*.service_key' => ['required', 'integer', 'distinct'],
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

    private function validateServices(Validator $validator): void
    {
        $serviceIds = collect($this->input('services', []))->pluck('service_id')->all();
        $services = Service::query()->whereIn('id', $serviceIds)->get()->keyBy('id');

        foreach ($this->input('services', []) as $index => $taskService) {
            $service = $services->get((int) $taskService['service_id']);

            if (! $service || ! $service->is_active) {
                $validator->errors()->add("services.$index.service_id", __('api.not_found'));
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
