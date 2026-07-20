<?php

namespace App\Modules\V1\Brands\Presentation\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Shared\Presentation\Http\Requests\Concerns\ValidatesSingleMediaUpdate;

class UpdateBrandRequest extends FormRequest
{
    use ValidatesSingleMediaUpdate;
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
            'translations' => 'sometimes|array',
            'translations.en.name' => 'sometimes|required|string|max:255',
            'translations.ar.name' => 'sometimes|required|string|max:255',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ...$this->singleMediaActionRules('logo_action'),
            'is_active' => 'sometimes|boolean'
        ];
    }

    public function after(): array
    {
        return [$this->validateSingleMediaUpdate('logo', 'logo_action')];
    }
}
