<?php

namespace Tests\Unit;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Tasks\Presentation\Http\Requests\StoreCompanyTaskRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StoreCompanyTaskRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_company_task_payload_uses_service_key_and_exposes_resolved_service_id(): void
    {
        $service = Service::query()->create([
            'key' => ServiceTypeEnum::PRIMARY_DISPLAY->value,
            'minimum_price' => 50,
            'minimum_execution_time' => 30,
            'is_active' => true,
        ]);

        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => 'acme@example.com',
            'phone' => '01000000000',
            'cr_number' => 'CR-100',
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);

        $product = Product::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Shelf Product',
            'sku' => 'SKU-100',
            'is_active' => true,
        ]);

        Carbon::setTestNow('2026-06-19 10:00:00');

        $request = StoreCompanyTaskRequest::create(
            '/company/tasks',
            'POST',
            [
                'date' => '2026-06-20',
                'location' => [
                    'latitude' => 30.0444,
                    'longitude' => 31.2357,
                ],
                'services' => [
                    [
                        'service_key' => ServiceTypeEnum::PRIMARY_DISPLAY->value,
                        'price' => 50,
                        'execution_time_minutes' => 30,
                        'products' => [
                            ['product_id' => $product->id],
                        ],
                    ],
                ],
            ],
            [],
            [
                'services' => [
                    [
                        'request_files' => [
                            'planogram_files' => [UploadedFile::fake()->create('planogram.pdf', 1, 'application/pdf')],
                        ],
                    ],
                ],
            ]
        );

        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);
        $request->validateResolved();

        $validated = $request->validated();

        $this->assertSame(ServiceTypeEnum::PRIMARY_DISPLAY->value, $validated['services'][0]['service_key']);
        $this->assertSame($service->id, $validated['services'][0]['service_id']);
    }

    public function test_company_task_payload_rejects_execution_time_from_request(): void
    {
        Carbon::setTestNow('2026-06-19 10:00:00');

        $request = StoreCompanyTaskRequest::create('/company/tasks', 'POST', [
            'date' => '2026-06-19',
            'execution_time' => '14:00',
        ]);

        $validator = validator($request->all(), (new StoreCompanyTaskRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('execution_time', $validator->errors()->messages());
    }

    public function test_company_task_date_must_be_today_or_tomorrow(): void
    {
        Carbon::setTestNow('2026-06-19 10:00:00');

        $rules = (new StoreCompanyTaskRequest())->rules();

        $futureValidator = validator(['date' => '2026-06-21'], ['date' => $rules['date']]);
        $todayValidator = validator(['date' => '2026-06-19'], ['date' => $rules['date']]);
        $tomorrowValidator = validator(['date' => '2026-06-20'], ['date' => $rules['date']]);

        $this->assertTrue($futureValidator->fails());
        $this->assertFalse($todayValidator->fails());
        $this->assertFalse($tomorrowValidator->fails());
    }
}
