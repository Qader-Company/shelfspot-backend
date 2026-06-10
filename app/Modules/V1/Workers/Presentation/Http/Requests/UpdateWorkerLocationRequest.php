<?php

namespace App\Modules\V1\Workers\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkerLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
