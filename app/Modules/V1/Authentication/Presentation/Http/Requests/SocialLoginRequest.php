<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return array_merge(
            [
                'token' => ['required', 'string'],
                'name' => ['sometimes', 'string', 'max:255'],
            ],
            config('social_auth.portal_profile_rules.' . $this->route('type'), [])
        );
    }
}
