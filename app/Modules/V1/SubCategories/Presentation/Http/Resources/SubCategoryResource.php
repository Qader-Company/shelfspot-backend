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
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->toISOString()),
            'purge_status' => $this->when($this->purge_status, $this->purge_status),
            'purge_status_label' => $this->when($this->purge_status, fn () => __('enums.catalog_purge_status.'.$this->purge_status)),
            'name' => $this->name,
            'image' => $this->whenLoaded(
                'media',
                $this->getMedia('image')->first()?->getUrl() ?? ''
            ),
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
