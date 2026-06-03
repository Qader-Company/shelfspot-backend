<?php

namespace App\Modules\V1\SubCategories\Presentation\Http\Resources;

use App\Modules\V1\Brands\Presentation\Http\Resources\BrandResource;
use App\Modules\V1\Categories\Presentation\Http\Resources\CategoryResource;
use App\Modules\V1\SubBrands\Presentation\Http\Resources\SubBrandResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->getMedia('image')->first()?->getUrl(),
            'active' => (bool) $this->is_active,
            'brand' => $this->whenLoaded(
                relationship: 'brand',
                value:fn() =>new BrandResource($this->brand)
            ),
            'sub_brand' => $this->whenLoaded(
                relationship: 'subBrand',
                value:fn() =>new SubBrandResource($this->subBrand)
            ),
            'category' => $this->whenLoaded(
                relationship: 'category',
                value:fn() =>new CategoryResource($this->category)
            ),
        ];
    }
}
