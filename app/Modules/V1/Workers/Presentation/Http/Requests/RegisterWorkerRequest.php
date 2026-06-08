<?php

namespace App\Modules\V1\Workers\Presentation\Http\Requests;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('type', PortalTypeEnum::WORKER->value),
            ],
            'phone' => ['required', 'string', 'max:255', 'unique:workers,phone'],
            'password' => [
                Password::min(8)->mixedCase(),
                'string',
                'required',
                'confirmed',
            ],
        ];
    }
}
