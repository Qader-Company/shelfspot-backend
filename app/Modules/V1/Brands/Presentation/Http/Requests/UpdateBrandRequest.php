<?php

namespace App\Modules\V1\Brands\Presentation\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'active' => 'sometimes|boolean'
        ];
    }
}
