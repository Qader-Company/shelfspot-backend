<?php

namespace App\Modules\V1\Companies\Presentation\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterCompanyRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:companies,email',
            'phone' => 'required|string|max:255|unique:companies,phone',
            'password' => [
                Password::min(8)->mixedCase(),
                'string',
                'required',
                'confirmed'
            ],
            'cr_number' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
        ];
    }
}
