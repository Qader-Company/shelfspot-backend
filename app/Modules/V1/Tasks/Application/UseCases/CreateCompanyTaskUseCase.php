<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Application\Support\TaskExpiryDate;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceProduct;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateCompanyTaskUseCase
{
    private const FIXED_EXECUTION_TIME = '00:00:00';

    public function __construct(
        private readonly TenantContextInterface $tenantContext,
        private readonly ChargeTaskWalletUseCase $chargeTaskWalletUseCase,
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder,
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function execute(array $data, User $actor, array $files = []): Task
    {
        return DB::transaction(function () use ($data, $actor, $files) {
            $taskServices = $data['services'];
            $totalPrice = collect($taskServices)->sum(fn (array $service) => (float) $service['price']);

            $task = $this->taskRepository->create([
                'company_id' => $this->tenantContext->getCompanyId(),
                'date' => $data['date'],
                'execution_time' => self::FIXED_EXECUTION_TIME,
                'expires_at' => TaskExpiryDate::fromExecutionDate($data['date']),
                'latitude' => $data['location']['latitude'],
                'longitude' => $data['location']['longitude'],
                'location_name' => $data['location']['location_name'] ?? null,
                'address' => $data['location']['address'] ?? null,
                'total_price' => $totalPrice,
                'notes' => $data['notes'] ?? null,
                'status' => TaskStatusEnum::DRAFT,
                'payment_status' => TaskPaymentStatusEnum::PENDING,
                'created_by' => $actor->id,
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
                $this->chargeTaskWalletUseCase->execute($task, $actor->id);
                $task->refresh();
            } catch (InvalidArgumentException) {
                $task->forceFill(['payment_status' => TaskPaymentStatusEnum::FAILED])->save();

                return $task->load($this->taskRepository->relations());
            }

            $task->forceFill(['status' => TaskStatusEnum::PENDING])->save();

            $this->statusHistoryRecorder->record(
                task: $task,
                fromStatus: TaskStatusEnum::DRAFT,
                toStatus: TaskStatusEnum::PENDING,
                actor: $actor,
                meta: ['payment_status' => $task->payment_status?->value]
            );

            return $task->load($this->taskRepository->relations());
        });
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
