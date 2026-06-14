<?php

namespace Tests\Unit;

use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Tasks\Application\DTOs\TaskServiceValidationData;
use App\Modules\V1\Tasks\Application\Validation\Strategies\FreshnessReportValidationStrategy;
use App\Modules\V1\Tasks\Application\Validation\Strategies\SecondaryDisplayExecutionValidationStrategy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TaskServiceValidationStrategyTest extends TestCase
{
    public function test_freshness_details_are_validated_on_each_product_not_request_details(): void
    {
        $validator = Validator::make([], []);
        $service = new Service(['key' => ServiceTypeEnum::FRESHNESS_REPORT->value]);
        $taskService = [
            'request_details' => [
                'expected_quantity' => 5,
            ],
            'products' => [
                [
                    'product_id' => 10,
                    'product_details' => [
                        'expected_quantity' => 0,
                        'unexpected_field' => 'wrong place',
                    ],
                ],
            ],
        ];

        app(FreshnessReportValidationStrategy::class)->validate(
            new TaskServiceValidationData(0, $taskService, $service, [
                'planogram_files' => [UploadedFile::fake()->create('planogram.pdf', 1, 'application/pdf')],
            ]),
            $validator,
        );

        $this->assertTrue($validator->errors()->has('services.0.request_details.expected_quantity'));
        $this->assertTrue($validator->errors()->has('services.0.products.0.product_details.expected_quantity'));
        $this->assertTrue($validator->errors()->has('services.0.products.0.product_details.unexpected_field'));
    }

    public function test_secondary_display_keeps_optional_job_order_file_rule(): void
    {
        $validator = Validator::make([], []);
        $service = new Service(['key' => ServiceTypeEnum::SECONDARY_DISPLAY_EXECUTION->value]);

        app(SecondaryDisplayExecutionValidationStrategy::class)->validate(
            new TaskServiceValidationData(0, [
                'products' => [
                    ['product_id' => 10],
                ],
            ], $service, [
                'planogram_files' => [UploadedFile::fake()->create('planogram.pdf', 1, 'application/pdf')],
            ]),
            $validator,
        );

        $this->assertFalse($validator->errors()->has('services.0.request_files.planogram_files'));
        $this->assertFalse($validator->errors()->has('services.0.request_files.job_order_files'));
    }
}
