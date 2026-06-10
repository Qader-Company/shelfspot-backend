<?php

namespace Tests\Unit;

use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Services\Presentation\Http\Resources\ServiceResource;
use Illuminate\Http\Request;
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
            $this->assertArrayHasKey('planogram_files', $catalog['request_form']['fields']);
            $this->assertSame('array<file>', $catalog['request_form']['fields']['planogram_files']['type']);
            $this->assertTrue($catalog['request_form']['fields']['planogram_files']['required']);
        }
    }

    public function test_service_catalog_captures_worker_submission_requirements(): void
    {
        $primaryDisplay = ServiceTypeEnum::PRIMARY_DISPLAY->submissionForm()['fields'];
        $secondaryDisplay = ServiceTypeEnum::SECONDARY_DISPLAY_EXECUTION->submissionForm()['fields'];
        $availability = ServiceTypeEnum::ON_SHELF_AVAILABILITY->submissionForm()['fields']['items']['item_fields'];
        $visibility = ServiceTypeEnum::INSTORE_VISIBILITY->submissionForm()['fields'];
        $freshness = ServiceTypeEnum::FRESHNESS_REPORT->submissionForm()['fields']['items']['item_fields'];

        $this->assertArrayHasKey('before_picture_files', $primaryDisplay);
        $this->assertSame('array<file>', $primaryDisplay['before_picture_files']['type']);
        $this->assertArrayHasKey('after_picture_files', $primaryDisplay);
        $this->assertArrayHasKey('before_picture_files', $secondaryDisplay);
        $this->assertArrayHasKey('after_picture_files', $secondaryDisplay);
        $this->assertSame(['available', 'unavailable'], $availability['availability']['values']);
        $this->assertArrayHasKey('picture_files', $visibility);
        $this->assertArrayHasKey('quantity', $freshness);
        $this->assertArrayHasKey('expiry_date', $freshness);
    }

    public function test_service_resource_returns_request_and_submit_forms(): void
    {
        $service = new Service([
            'key' => ServiceTypeEnum::PRIMARY_DISPLAY->value,
            'minimum_price' => 50,
            'minimum_execution_time' => 30,
            'is_active' => true,
        ]);
        $service->id = 1;

        $payload = (new ServiceResource($service))->toArray(Request::create('/'));

        $this->assertArrayHasKey('request_form', $payload);
        $this->assertArrayHasKey('submit_form', $payload);
        $this->assertArrayHasKey('submission_form', $payload);
        $this->assertSame(ServiceTypeEnum::PRIMARY_DISPLAY->requestForm(), $payload['request_form']);
        $this->assertSame(ServiceTypeEnum::PRIMARY_DISPLAY->submitForm(), $payload['submit_form']);
        $this->assertSame($payload['submit_form'], $payload['submission_form']);
    }

    public function test_service_type_labels_are_translated_for_english_and_arabic(): void
    {
        app()->setLocale('en');
        $this->assertSame('Home-shelf / Primary Display', ServiceTypeEnum::PRIMARY_DISPLAY->label());

        app()->setLocale('ar');
        $this->assertSame('العرض الأساسي على الرف', ServiceTypeEnum::PRIMARY_DISPLAY->label());
    }
}
