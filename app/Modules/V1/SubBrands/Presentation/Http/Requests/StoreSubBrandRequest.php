<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Requests;

use App\Modules\Shared\Support\Traits\ValidatesTenantOwnership;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubBrandRequest extends FormRequest
{
    use ValidatesTenantOwnership;

    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'brand_id' => ['required', $this->existsInCurrentCompany('brands')],
            'name' => 'required|string|max:255',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'required|boolean',
        ];
    }
}
