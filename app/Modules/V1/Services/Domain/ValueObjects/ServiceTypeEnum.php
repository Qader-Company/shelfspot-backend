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

    public function catalog(): array
    {
        return config("shelfspot_services.catalog.{$this->value}", []);
    }

    public function requestForm(): array
    {
        return $this->catalog()['request_form'] ?? [];
    }

    public function submissionForm(): array
    {
        return $this->catalog()['submission_form'] ?? [];
    }

    public function productDetailsForm(): array
    {
        return $this->catalog()['product_details_form'] ?? [];
    }

}
