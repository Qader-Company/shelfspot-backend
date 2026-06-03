<?php

namespace App\Modules\V1\Services\Presentation\Http\Requests;

use App\Modules\V1\Services\Domain\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
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
            'translations' => 'sometimes|array',
            'translations.*.description' => 'sometimes|string|max:255',
            'minimum_price' => 'sometimes|numeric|min:0',
            'minimum_execution_time' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
