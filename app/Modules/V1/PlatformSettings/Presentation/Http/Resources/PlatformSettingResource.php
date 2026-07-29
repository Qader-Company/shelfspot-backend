<?php

namespace App\Modules\V1\PlatformSettings\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
        ];
    }
}
