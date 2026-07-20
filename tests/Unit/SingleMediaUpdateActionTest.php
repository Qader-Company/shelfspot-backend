<?php

namespace Tests\Unit;

use App\Modules\Shared\Presentation\Http\Requests\Concerns\ValidatesSingleMediaUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SingleMediaUpdateActionTest extends TestCase
{
    public function test_omitting_the_action_and_file_keeps_the_existing_media(): void
    {
        $this->assertFalse($this->validator()->fails());
    }

    public function test_replace_requires_a_new_file(): void
    {
        $validator = $this->validator(['image_action' => 'replace']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('image', $validator->errors()->toArray());
    }

    public function test_keep_and_remove_reject_an_uploaded_file(): void
    {
        foreach (['keep', 'remove'] as $action) {
            $validator = $this->validator(
                ['image_action' => $action],
                ['image' => UploadedFile::fake()->image('image.jpg')],
            );

            $this->assertTrue($validator->fails(), $action);
            $this->assertArrayHasKey('image', $validator->errors()->toArray(), $action);
        }
    }

    public function test_replace_accepts_a_new_file(): void
    {
        $validator = $this->validator(
            ['image_action' => 'replace'],
            ['image' => UploadedFile::fake()->image('image.jpg')],
        );

        $this->assertFalse($validator->fails());
    }

    private function validator(array $input = [], array $files = []): \Illuminate\Validation\Validator
    {
        $request = new class extends FormRequest
        {
            use ValidatesSingleMediaUpdate;

            public function rules(): array
            {
                return [
                    'image' => ['nullable', 'image'],
                    ...$this->singleMediaActionRules('image_action'),
                ];
            }

            public function after(): array
            {
                return [$this->validateSingleMediaUpdate('image', 'image_action')];
            }
        };

        $request->initialize([], $input, [], [], $files, ['REQUEST_METHOD' => 'PATCH']);

        $validator = Validator::make($request->all(), $request->rules());

        foreach ($request->after() as $after) {
            $validator->after($after);
        }

        return $validator;
    }
}
