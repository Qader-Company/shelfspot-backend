<?php

namespace App\Modules\V1\Companies\Presentation\Http\Requests;

class UpdateCompanyProfileRequest extends UpdateCompanyRequest
{
    public function rules(): array
    {
        return [
            ...array_diff_key(parent::rules(), array_flip(['is_active'])),
            'is_active' => ['prohibited'],
        ];
    }
}
