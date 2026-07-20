<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanUpdateTaskRule;
use App\Modules\V1\Tasks\Application\Support\TaskExpiryDate;
use App\Modules\V1\Tasks\Application\Validation\TaskServiceValidationGenerator;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateCompanyTaskUseCase
{
    private const FIXED_EXECUTION_TIME = '00:00:00';

    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskServiceValidationGenerator $taskServiceValidationGenerator,
    ) {}

    public function execute(Task $task, array $data, array $files = []): Task
    {

        return DB::transaction(function () use ($task, $data, $files) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate(
                $task->id,
                ['services.products', 'services.media'],
            );
            CanUpdateTaskRule::validate($lockedTask);

            $preparedServices = [];

            if (array_key_exists('services', $data)) {
                $preparedServices = $this->prepareTaskServices($lockedTask, $this->withCatalogPrices($data['services']));
                $this->validateFinalServiceState($preparedServices, $files);
            }

            $this->updateTaskAttributes($lockedTask, $data, $preparedServices);

            if ($preparedServices !== []) {
                $this->reconcileTaskServices($lockedTask, $preparedServices, $files);
            }

            return $lockedTask->refresh()->load($this->taskRepository->detailRelations());
        });
    }

    private function updateTaskAttributes(Task $task, array $data, array $preparedServices): void
    {
        $attributes = [];

        if (array_key_exists('date', $data)) {
            $attributes['date'] = $data['date'];
            $attributes['execution_time'] = self::FIXED_EXECUTION_TIME;
            $attributes['expires_at'] = TaskExpiryDate::fromExecutionDate($data['date']);
        }

        if (array_key_exists('location', $data)) {
            $attributes['latitude'] = $data['location']['latitude'];
            $attributes['longitude'] = $data['location']['longitude'];
            $attributes['location_name'] = $data['location']['location_name'] ?? null;
            $attributes['address'] = $data['location']['address'] ?? null;
        }

        if (array_key_exists('notes', $data)) {
            $attributes['notes'] = $data['notes'];
        }

        if ($preparedServices !== []) {
            $attributes['total_price'] = collect($preparedServices)->sum(
                fn (array $service) => (float) $service['data']['price'],
            );
        }

        if ($attributes !== []) {
            $task->forceFill($attributes)->save();
        }
    }

    private function prepareTaskServices(Task $task, array $taskServices): array
    {
        $existingById = $task->services->keyBy('id');
        $existingByServiceId = $task->services->keyBy('service_id');
        $errors = [];
        $preparedServices = [];

        foreach ($taskServices as $index => $serviceData) {
            $taskServiceId = $serviceData['task_service_id'] ?? null;
            $existingTaskService = $taskServiceId !== null ? $existingById->get((int) $taskServiceId) : null;

            if ($taskServiceId !== null && $existingTaskService === null) {
                $errors["services.$index.task_service_id"][] = __('api.not_found');
                continue;
            }

            if ($existingTaskService !== null && (int) $existingTaskService->service_id !== (int) $serviceData['service_id']) {
                $errors["services.$index.service_key"][] = __('validation.in');
                continue;
            }

            if ($taskServiceId === null && $existingByServiceId->has((int) $serviceData['service_id'])) {
                $errors["services.$index.task_service_id"][] = __('validation.required');
                continue;
            }

            $keptAttachmentIds = collect($serviceData['keep_attachment_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
            $existingMediaById = $existingTaskService?->media()->get()->keyBy('id') ?? collect();
            $unknownAttachmentIds = array_values(array_diff($keptAttachmentIds, $existingMediaById->keys()->map(fn ($id) => (int) $id)->all()));

            if ($unknownAttachmentIds !== []) {
                $errors["services.$index.keep_attachment_ids"][] = __('api.not_found');
                continue;
            }

            $preparedServices[] = [
                'index' => $index,
                'data' => $serviceData,
                'task_service' => $existingTaskService,
                'kept_attachment_ids' => $keptAttachmentIds,
                'existing_media_by_id' => $existingMediaById,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $preparedServices;
    }

    private function validateFinalServiceState(array $preparedServices, array $files): void
    {
        $validator = Validator::make([], []);

        foreach ($preparedServices as $preparedService) {
            $existingFilesByField = $preparedService['existing_media_by_id']
                ->only($preparedService['kept_attachment_ids'])
                ->groupBy(fn ($media) => $media->getCustomProperty('field'))
                ->map(fn ($media) => $media->all())
                ->all();

            $service = Service::query()->findOrFail($preparedService['data']['service_id']);

            $this->taskServiceValidationGenerator->validate(
                index: $preparedService['index'],
                taskService: $preparedService['data'],
                service: $service,
                filesByField: Arr::get($files, "services.{$preparedService['index']}.request_files", []),
                validator: $validator,
                existingFilesByField: $existingFilesByField,
            );
        }

        if ($validator->errors()->isNotEmpty()) {
            throw ValidationException::withMessages($validator->errors()->messages());
        }
    }

    private function reconcileTaskServices(Task $task, array $preparedServices, array $files): void
    {
        $retainedTaskServiceIds = [];

        foreach ($preparedServices as $preparedService) {
            $taskService = $preparedService['task_service'] ?? $this->createTaskService($task, $preparedService);
            $this->updateTaskService($taskService, $preparedService);
            $this->reconcileProducts($taskService, $preparedService['data']['products']);
            $this->reconcileAttachments(
                $taskService,
                $preparedService['kept_attachment_ids'],
                Arr::get($files, "services.{$preparedService['index']}.request_files", []),
            );

            $retainedTaskServiceIds[] = $taskService->id;
        }

        $task->services
            ->reject(fn (TaskService $taskService) => in_array($taskService->id, $retainedTaskServiceIds, true))
            ->each(function (TaskService $taskService): void {
                $taskService->media()->get()->each(fn ($media) => $media->delete());
                $taskService->delete();
            });
    }

    private function createTaskService(Task $task, array $preparedService): TaskService
    {
        $taskService = new TaskService;
        $taskService->task_id = $task->id;
        $taskService->status = TaskServiceStatusEnum::PENDING;

        return $taskService;
    }

    private function updateTaskService(TaskService $taskService, array $preparedService): void
    {
        $serviceData = $preparedService['data'];

        $taskService->forceFill([
            'service_id' => $serviceData['service_id'],
            'execution_instructions' => $serviceData['execution_instructions'] ?? null,
            'request_details' => isset($serviceData['request_details']) ? json_encode($serviceData['request_details']) : null,
            'unit_price' => $serviceData['price'],
            'sort_order' => $preparedService['index'],
        ])->save();
    }

    private function reconcileProducts(TaskService $taskService, array $products): void
    {
        $productIds = collect($products)->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        $taskService->products()->whereNotIn('product_id', $productIds)->delete();

        foreach ($products as $product) {
            $taskService->products()->updateOrCreate(
                ['product_id' => $product['product_id']],
                ['product_details' => $product['product_details'] ?? null],
            );
        }
    }

    private function reconcileAttachments(TaskService $taskService, array $keptAttachmentIds, array $filesByField): void
    {
        $taskService->media()
            ->get()
            ->reject(fn ($media) => in_array((int) $media->id, $keptAttachmentIds, true))
            ->each(fn ($media) => $media->delete());

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

    private function withCatalogPrices(array $taskServices): array
    {
        $pricesByServiceId = Service::query()
            ->whereIn('id', collect($taskServices)->pluck('service_id'))
            ->pluck('price', 'id');

        return collect($taskServices)
            ->map(function (array $taskService) use ($pricesByServiceId) {
                $taskService['price'] = $pricesByServiceId->get($taskService['service_id']);

                return $taskService;
            })
            ->all();
    }
}
