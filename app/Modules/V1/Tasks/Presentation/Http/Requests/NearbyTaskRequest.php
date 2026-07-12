<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NearbyTaskRequest extends FormRequest
{
    public const DEFAULT_RADIUS_KM = 5;

    public const MAX_RADIUS_KM = 30;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'radius_km' => ['sometimes', 'numeric', 'in:5,10,15,20,25,30', 'max:'.self::MAX_RADIUS_KM],
            'execution_date' => ['sometimes', 'date'],
        ];
    }

    public function radiusKilometers(): float
    {
        return (float) $this->validated('radius_km', self::DEFAULT_RADIUS_KM);
    }
}
