<?php

namespace App\Modules\V1\Categories\Presentation\Http\Requests;

use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use App\Modules\V1\Categories\Domain\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'translations' => 'sometimes|array',
            'translations.en.name' => 'sometimes|required|string|max:255',
            'translations.ar.name' => 'sometimes|required|string|max:255',
            'brand_id' => ['nullable', new ExistsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', new ExistsInCurrentCompany('sub_brands')],
            'is_active' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

}
