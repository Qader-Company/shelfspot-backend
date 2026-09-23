<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Requests;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends FormRequest
{
    public function rules(): array
    {
        $isWorker = $this->route('type') === PortalTypeEnum::WORKER->value;

        return array_merge(
            [
                'token' => ['required', 'string'],
                'name' => ['sometimes', 'string', 'max:255'],
                'device_token' => [Rule::prohibitedIf(! $isWorker), 'nullable', 'string', 'max:512'],
                'device_type' => [Rule::prohibitedIf(! $isWorker || ! $this->filled('device_token')), 'nullable', 'in:android,ios'],
                'device_name' => [Rule::prohibitedIf(! $isWorker || ! $this->filled('device_token')), 'nullable', 'string', 'max:255'],
            ],
            config('social_auth.portal_profile_rules.'.$this->route('type'), [])
        );
    }
}
