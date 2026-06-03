<?php

namespace App\Modules\V1\Companies\Presentation\Http\Requests;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = $this->route('company');
        return [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255|unique:companies,phone,'.$companyId,
            'cr_number' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'industry' => [
                'sometimes',
                'string',
                Rule::in(CompanyIndustryEnum::values())
            ],
        ];
    }
}
