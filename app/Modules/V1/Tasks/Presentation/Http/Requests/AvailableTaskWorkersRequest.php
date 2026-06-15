<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvailableTaskWorkersRequest extends FormRequest
{
    public const DEFAULT_RADIUS_KM = 5;
    public const MAX_RADIUS_KM = 100;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'radius_km' => ['sometimes', 'numeric', 'min:0.1', 'max:'.self::MAX_RADIUS_KM],
        ];
    }

    public function radiusKilometers(): float
    {
        return (float) $this->validated('radius_km', self::DEFAULT_RADIUS_KM);
    }
}
