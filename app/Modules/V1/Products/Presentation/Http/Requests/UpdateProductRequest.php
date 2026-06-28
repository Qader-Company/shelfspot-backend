<?php

namespace App\Modules\V1\Products\Presentation\Http\Requests;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'brand_id' => ['nullable', new ExistsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', new ExistsInCurrentCompany('sub_brands')],
            'category_id' => ['nullable', new ExistsInCurrentCompany('categories')],
            'sub_category_id' => ['nullable', new ExistsInCurrentCompany('sub_categories')],
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'barcode' => ['nullable', 'string', 'max:255', $this->uniqueBarcodeRule()],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'sometimes|boolean',
        ];
    }


    private function uniqueBarcodeRule(): Unique
    {
        return Rule::unique('products', 'barcode')
            ->where(fn ($query) => $query->where('company_id', app(TenantContextInterface::class)->getCompanyId()))
            ->ignore($this->route('id'));
    }

}
