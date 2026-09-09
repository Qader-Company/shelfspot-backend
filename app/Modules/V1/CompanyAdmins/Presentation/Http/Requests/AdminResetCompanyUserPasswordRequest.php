<?php

namespace App\Modules\V1\CompanyAdmins\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminResetCompanyUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
