<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceProduct;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateCompanyTaskUseCase
{
    private const FIXED_EXECUTION_TIME = '00:00:00';

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
                'execution_time' => self::FIXED_EXECUTION_TIME,
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

            $taskServiceModels = $this->createTaskServices($task, $taskServices);
            $this->createTaskServiceProducts($taskServiceModels, $taskServices);

            foreach ($taskServices as $index => $serviceData) {
                $taskService = $taskServiceModels->get((int) $serviceData['service_id']);

                if ($taskService === null) {
                    continue;
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
            'services.products.product.media',
            'services.products.product.brand.media',
            'services.products.product.subBrand.media',
            'services.products.product.category',
            'services.products.product.subCategory',
            'creator',
            'assignedWorker',
        ];
    }

    private function createTaskServices(Task $task, array $taskServices): Collection
    {
        $now = now();

        TaskService::query()->insert(
            collect($taskServices)
                ->map(fn (array $serviceData, int $index) => [
                    'task_id' => $task->id,
                    'service_id' => $serviceData['service_id'],
                    'execution_instructions' => $serviceData['execution_instructions'] ?? null,
                    'request_details' => isset($serviceData['request_details']) ? json_encode($serviceData['request_details']) : null,
                    'unit_price' => $serviceData['price'],
                    'status' => TaskServiceStatusEnum::PENDING->value,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all()
        );

        return TaskService::query()
            ->where('task_id', $task->id)
            ->whereIn('service_id', collect($taskServices)->pluck('service_id'))
            ->get()
            ->keyBy(fn (TaskService $taskService) => (int) $taskService->service_id);
    }

    private function createTaskServiceProducts(Collection $taskServiceModels, array $taskServices): void
    {
        $now = now();
        $productRows = collect($taskServices)
            ->flatMap(function (array $serviceData) use ($taskServiceModels, $now) {
                $taskService = $taskServiceModels->get((int) $serviceData['service_id']);

                if ($taskService === null) {
                    return [];
                }

                return collect($serviceData['products'])->map(fn (array $product) => [
                    'task_service_id' => $taskService->id,
                    'product_id' => $product['product_id'],
                    'product_details' => isset($product['product_details']) ? json_encode($product['product_details']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            })
            ->all();

        if ($productRows !== []) {
            TaskServiceProduct::query()->insert($productRows);
        }
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
