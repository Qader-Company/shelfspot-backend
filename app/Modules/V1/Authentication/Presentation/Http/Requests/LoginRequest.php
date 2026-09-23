<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Requests;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
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
        $isWorker = $this->route('type') === PortalTypeEnum::WORKER->value;

        return array_merge(
            (new EmailValidationRequest)->rules(),
            [
                'password' => ['required', 'string', 'min:6'],
                'device_token' => [Rule::prohibitedIf(! $isWorker), 'nullable', 'string', 'max:512'],
                'device_type' => [Rule::prohibitedIf(! $isWorker || ! $this->filled('device_token')), 'nullable', 'in:android,ios'],
                'device_name' => [Rule::prohibitedIf(! $isWorker || ! $this->filled('device_token')), 'nullable', 'string', 'max:255'],
            ],
        );
    }
}
