<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateCompanyTaskUseCase
{
    public function __construct(
        private readonly TenantContextInterface $tenantContext,
        private readonly ChargeTaskWalletUseCase $chargeTaskWalletUseCase,
    ) {
    }

    public function execute(array $data, array $files = []): Task
    {
        return DB::transaction(function () use ($data, $files) {
            $taskServices = $data['services'];
            $subtotal = collect($taskServices)->sum(fn (array $service) => (float) $service['price']);
            $estimatedDuration = collect($taskServices)->sum(fn (array $service) => (int) $service['execution_time_minutes']);

            $task = Task::create([
                'company_id' => $this->tenantContext->getCompanyId(),
                'date' => $data['date'],
                'execution_time' => $data['execution_time'],
                'estimated_duration_minutes' => $estimatedDuration,
                'latitude' => $data['location']['latitude'],
                'longitude' => $data['location']['longitude'],
                'location_name' => $data['location']['location_name'] ?? null,
                'address' => $data['location']['address'] ?? null,
                'subtotal' => $subtotal,
                'total_price' => $subtotal,
                'notes' => $data['notes'] ?? null,
                'status' => TaskStatusEnum::DRAFT,
                'payment_status' => TaskPaymentStatusEnum::PENDING,
                'created_by' => auth()->id(),
            ]);

            foreach ($taskServices as $index => $serviceData) {
                $taskService = $task->services()->create([
                    'service_id' => $serviceData['service_id'],
                    'execution_instructions' => $serviceData['execution_instructions'] ?? null,
                    'request_details' => $serviceData['request_details'],
                    'unit_price' => $serviceData['price'],
                    'status' => TaskServiceStatusEnum::PENDING,
                    'sort_order' => $index,
                ]);

                foreach ($serviceData['products'] as $product) {
                    $taskService->products()->create([
                        'product_id' => $product['product_id'],
                        'product_details' => $product['product_details'] ?? null,
                    ]);
                }

                $this->attachRequestFiles($taskService, Arr::get($files, "services.$index.request_files", []));
            }

            try {
                $this->chargeTaskWalletUseCase->execute($task);
            } catch (InvalidArgumentException $exception) {
                $task->forceFill(['payment_status' => TaskPaymentStatusEnum::FAILED])->save();

                throw ValidationException::withMessages([
                    'wallet' => $exception->getMessage(),
                ]);
            }

            $task->forceFill(['status' => TaskStatusEnum::ACTIVE])->save();

            return $task->load($this->relations());
        });
    }

    public function relations(): array
    {
        return [
            'services.service.translations',
            'services.products.product',
            'creator',
            'assignedWorker',
        ];
    }

    private function attachRequestFiles(TaskService $taskService, array $filesByField): void
    {
        foreach ($filesByField as $field => $files) {
            foreach (Arr::wrap($files) as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $taskService
                    ->addMedia($file)
                    ->withCustomProperties(['field' => $field])
                    ->toMediaCollection($field);
            }
        }
    }
}
