<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Requests;

use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:255'],
            'cr_number' => ['sometimes', 'string', 'max:255'],
            'industry' => ['sometimes', 'string', Rule::in(CompanyIndustryEnum::values())],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }
}
