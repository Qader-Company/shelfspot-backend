<?php

namespace Tests\Unit;

use App\Modules\V1\Tasks\Application\Validation\DynamicFormValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DynamicFormValidatorTest extends TestCase
{
    public function test_it_builds_nested_submission_rules_from_dynamic_fields(): void
    {
        $fields = [
            'items' => [
                'type' => 'array',
                'required' => true,
                'min_items' => 1,
                'item_fields' => [
                    'availability' => ['type' => 'enum', 'required' => true, 'values' => ['available', 'unavailable']],
                    'quantity' => ['type' => 'integer', 'required' => true, 'min' => 0],
                    'expiry_date' => ['type' => 'date', 'required' => true],
                ],
            ],
        ];

        $validator = app(DynamicFormValidator::class);

        $validator->validate($fields, [
            'items' => [
                [
                    'availability' => 'available',
                    'quantity' => 0,
                    'expiry_date' => '2026-06-14',
                ],
            ],
        ]);

        $this->expectException(ValidationException::class);

        $validator->validate($fields, [
            'items' => [
                [
                    'availability' => 'missing',
                    'quantity' => -1,
                    'expiry_date' => 'not-a-date',
                ],
            ],
        ]);
    }

    public function test_it_validates_required_file_fields_with_a_custom_error_prefix(): void
    {
        $fields = [
            'before_picture_files' => [
                'type' => 'array<file>',
                'required' => true,
                'min_items' => 1,
            ],
        ];

        $validator = app(DynamicFormValidator::class);

        $validator->validateFiles($fields, [
            'before_picture_files' => [UploadedFile::fake()->image('before.jpg')],
        ], 'submission_files');

        try {
            $validator->validateFiles($fields, [], 'submission_files');
            $this->fail('Expected required file validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('submission_files.before_picture_files', $exception->errors());
        }
    }
}
