<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Requests;

use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubBrandRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'brand_id' => ['required', new ExistsInCurrentCompany('brands')],
            'translations' => 'required|array',
            'translations.en.name' => 'required|string|max:255',
            'translations.ar.name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'required|boolean',
        ];
    }

}
