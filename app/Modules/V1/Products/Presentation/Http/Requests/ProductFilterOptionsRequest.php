<?php

namespace App\Modules\V1\Products\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductFilterOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'sub_brand_id' => ['nullable', 'integer', 'exists:sub_brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'integer', 'exists:sub_categories,id'],
        ];
    }
}
