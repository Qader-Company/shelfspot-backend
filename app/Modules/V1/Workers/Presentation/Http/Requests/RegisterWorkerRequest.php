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
            'latitude' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'location_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
