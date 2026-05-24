<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'name' => $this->name,
            'logo' => $this->getMedia('logo')->first()?->getUrl(),
            'active' => (bool) $this->is_active,
        ];
    }
}
