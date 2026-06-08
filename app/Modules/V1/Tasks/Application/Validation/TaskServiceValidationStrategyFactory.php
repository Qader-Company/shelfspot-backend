<?php

namespace App\Modules\V1\Tasks\Application\Validation;

use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Tasks\Application\Validation\Strategies\FreshnessReportValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\InstoreVisibilityValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\OnShelfAvailabilityValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\PrimaryDisplayValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\SecondaryDisplayExecutionValidationStrategy;

class TaskServiceValidationStrategyFactory
{
    public function make(ServiceTypeEnum $serviceType): TaskServiceValidationStrategyInterface
    {
        return match ($serviceType) {
            ServiceTypeEnum::PRIMARY_DISPLAY => app(PrimaryDisplayValidationStrategy::class),
            ServiceTypeEnum::SECONDARY_DISPLAY_EXECUTION => app(SecondaryDisplayExecutionValidationStrategy::class),
            ServiceTypeEnum::ON_SHELF_AVAILABILITY => app(OnShelfAvailabilityValidationStrategy::class),
            ServiceTypeEnum::INSTORE_VISIBILITY => app(InstoreVisibilityValidationStrategy::class),
            ServiceTypeEnum::FRESHNESS_REPORT => app(FreshnessReportValidationStrategy::class),
        };
    }
}
