<?php

namespace App\Modules\V1\Products\Presentation\Http\Requests;

use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
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
            'brand_id' => ['required', 'integer', new ExistsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', 'integer', new ExistsInCurrentCompany('sub_brands')],
            'category_id' => ['nullable', 'integer', new ExistsInCurrentCompany('categories')],
            'sub_category_id' => ['nullable', 'integer', new ExistsInCurrentCompany('sub_categories')],
        ];
    }

}
