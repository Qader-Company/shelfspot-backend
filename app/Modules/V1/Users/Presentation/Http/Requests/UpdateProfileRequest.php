<?php

namespace App\Modules\V1\Users\Presentation\Http\Requests;

use App\Modules\V1\Users\Application\Profiles\ProfileHandlerFactory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $handler = app(ProfileHandlerFactory::class)->for($this->user());
        $rules = $handler->rules($this->user());

        foreach (array_diff(array_keys($this->all()), $handler->allowedFields()) as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
    }
}
