<?php

namespace App\Modules\V1\Users\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
        ];
    }
}
