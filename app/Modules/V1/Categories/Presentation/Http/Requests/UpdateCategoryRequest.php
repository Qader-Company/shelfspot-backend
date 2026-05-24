<?php

namespace App\Modules\V1\Categories\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'sub_brand_id' => 'nullable|exists:sub_brands,id',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
