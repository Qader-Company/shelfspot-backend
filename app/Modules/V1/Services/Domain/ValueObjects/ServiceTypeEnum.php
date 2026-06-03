<?php

namespace App\Modules\V1\Services\Domain\ValueObjects;

enum ServiceTypeEnum: string
{
    case PRIMARY_DISPLAY = 'primary_display';
    case SECONDARY_DISPLAY_EXECUTION = 'secondary_display_execution';
    case ON_SHELF_AVAILABILITY = 'on_shelf_availability';
    case INSTORE_VISIBILITY = 'instore_visibility';
    case FRESHNESS_REPORT = 'freshness_report';

    public function label(): string
    {
        return __("service.type.{$this->value}");
    }

}
