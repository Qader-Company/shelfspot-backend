<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Resources;

use App\Modules\V1\Brands\Presentation\Http\Resources\BrandResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->getMedia('logo')->first()?->getUrl(),
            'active' => (bool) $this->is_active,
            'brand' => $this->whenLoaded(
                relationship: 'brand',
                value:fn() =>new BrandResource($this->brand)
            ),
        ];
    }
}
