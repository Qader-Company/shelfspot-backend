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
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->toISOString()),
            'name' => $this->name,
            'logo' => $this->whenLoaded('media',$this->getMedia('logo')->first()?->getUrl()),
            'active' => (bool) $this->is_active,
            'brand' => $this->whenLoaded(
                relationship: 'brand',
                value:fn() =>new BrandResource($this->brand)
            ),
        ];
    }
}
