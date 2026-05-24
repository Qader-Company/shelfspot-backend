<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubBrandRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'required|boolean',
        ];
    }
}
