<?php

namespace App\Modules\V1\SubCategories\Presentation\Http\Requests;

use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use App\Modules\Shared\Presentation\Http\Requests\Concerns\ValidatesSingleMediaUpdate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubCategoryRequest extends FormRequest
{
    use ValidatesSingleMediaUpdate;
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'translations' => 'sometimes|array',
            'translations.en.name' => 'sometimes|required|string|max:255',
            'translations.ar.name' => 'sometimes|required|string|max:255',
            'brand_id' => ['nullable', new ExistsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', new ExistsInCurrentCompany('sub_brands')],
            'category_id' => ['sometimes', new ExistsInCurrentCompany('categories')],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ...$this->singleMediaActionRules('image_action'),
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function after(): array
    {
        return [$this->validateSingleMediaUpdate('image', 'image_action')];
    }

}
