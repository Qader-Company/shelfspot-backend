<?php

namespace Tests\Unit;

use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use Tests\TestCase;

class ServiceCatalogConfigTest extends TestCase
{
    public function test_service_catalog_defines_request_and_submission_forms_for_every_service_type(): void
    {
        foreach (ServiceTypeEnum::cases() as $serviceType) {
            $catalog = $serviceType->catalog();

            $this->assertNotEmpty($catalog, "Missing catalog config for {$serviceType->value}.");
            $this->assertArrayHasKey('minimum_price', $catalog);
            $this->assertArrayHasKey('minimum_execution_time', $catalog);
            $this->assertArrayHasKey('description', $catalog);
            $this->assertArrayHasKey('en', $catalog['description']);
            $this->assertArrayHasKey('ar', $catalog['description']);
            $this->assertArrayHasKey('request_form', $catalog);
            $this->assertArrayHasKey('submission_form', $catalog);
            $this->assertTrue($catalog['request_form']['requires_products']);
            $this->assertArrayHasKey('planogram_media_ids', $catalog['request_form']['fields']);
            $this->assertTrue($catalog['request_form']['fields']['planogram_media_ids']['required']);
        }
    }

    public function test_service_catalog_captures_worker_submission_requirements(): void
    {
        $primaryDisplay = ServiceTypeEnum::PRIMARY_DISPLAY->submissionForm()['fields'];
        $secondaryDisplay = ServiceTypeEnum::SECONDARY_DISPLAY_EXECUTION->submissionForm()['fields'];
        $availability = ServiceTypeEnum::ON_SHELF_AVAILABILITY->submissionForm()['fields']['items']['item_fields'];
        $visibility = ServiceTypeEnum::INSTORE_VISIBILITY->submissionForm()['fields'];
        $freshness = ServiceTypeEnum::FRESHNESS_REPORT->submissionForm()['fields']['items']['item_fields'];

        $this->assertArrayHasKey('before_picture_media_ids', $primaryDisplay);
        $this->assertArrayHasKey('after_picture_media_ids', $primaryDisplay);
        $this->assertArrayHasKey('before_picture_media_ids', $secondaryDisplay);
        $this->assertArrayHasKey('after_picture_media_ids', $secondaryDisplay);
        $this->assertSame(['available', 'unavailable'], $availability['availability']['values']);
        $this->assertArrayHasKey('picture_media_ids', $visibility);
        $this->assertArrayHasKey('quantity', $freshness);
        $this->assertArrayHasKey('expiry_date', $freshness);
    }

    public function test_service_type_labels_are_translated_for_english_and_arabic(): void
    {
        app()->setLocale('en');
        $this->assertSame('Home-shelf / Primary Display', ServiceTypeEnum::PRIMARY_DISPLAY->label());

        app()->setLocale('ar');
        $this->assertSame('العرض الأساسي على الرف', ServiceTypeEnum::PRIMARY_DISPLAY->label());
    }
}
