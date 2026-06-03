<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Requests;

use App\Modules\Shared\Support\Traits\ValidatesTenantOwnership;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubBrandRequest extends FormRequest
{
    use ValidatesTenantOwnership;

    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'brand_id' => ['sometimes', $this->existsInCurrentCompany('brands')],
            'name' => 'sometimes|string|max:255',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
