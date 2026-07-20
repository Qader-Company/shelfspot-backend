<?php

namespace App\Modules\V1\Tasks\Application\Validation;

use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Tasks\Application\DTOs\TaskServiceValidationData;
use App\Modules\V1\Tasks\Application\Validation\Strategies\AbstractTaskServiceValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\FreshnessReportValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\InstoreVisibilityValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\OnShelfAvailabilityValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\PrimaryDisplayValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\SecondaryDisplayExecutionValidationStrategy;
use Illuminate\Validation\Validator;

class TaskServiceValidationGenerator
{

    public function validate(
        int $index,
        array $taskService,
        Service $service,
        array $filesByField,
        Validator $validator,
        array $existingFilesByField = [],
    ): void
    {
        $this->strategyFactory($service->key)
            ->validate(
                new TaskServiceValidationData($index, $taskService, $service, $filesByField, $existingFilesByField),
                $validator,
            );
    }

    public function strategyFactory(ServiceTypeEnum $serviceKey): AbstractTaskServiceValidationStrategy
    {
        return match ($serviceKey) {
            ServiceTypeEnum::PRIMARY_DISPLAY => app(PrimaryDisplayValidationStrategy::class),
            ServiceTypeEnum::SECONDARY_DISPLAY_EXECUTION => app(SecondaryDisplayExecutionValidationStrategy::class),
            ServiceTypeEnum::ON_SHELF_AVAILABILITY => app(OnShelfAvailabilityValidationStrategy::class),
            ServiceTypeEnum::INSTORE_VISIBILITY => app(InstoreVisibilityValidationStrategy::class),
            ServiceTypeEnum::FRESHNESS_REPORT => app(FreshnessReportValidationStrategy::class),
        };
    }
}
