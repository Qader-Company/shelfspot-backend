<?php

namespace App\Modules\V1\Users\Presentation\Http\Requests;

use App\Modules\V1\Users\Application\Profiles\ProfileHandlerFactory;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'device_type' => ['nullable', 'in:android,ios'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
