<?php

namespace App\Modules\V1\Categories\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand_id' => $this->brand_id,
            'sub_brand_id' => $this->sub_brand_id,
            'active' => (bool) $this->is_active,
        ];
    }
}
