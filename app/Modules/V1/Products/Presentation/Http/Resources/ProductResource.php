<?php

namespace App\Modules\V1\Products\Presentation\Http\Resources;

use App\Modules\V1\Brands\Presentation\Http\Resources\BrandResource;
use App\Modules\V1\Categories\Presentation\Http\Resources\CategoryResource;
use App\Modules\V1\SubBrands\Presentation\Http\Resources\SubBrandResource;
use App\Modules\V1\SubCategories\Presentation\Http\Resources\SubCategoryResource;
use App\Modules\Shared\Presentation\Http\Resources\Concerns\IncludesTranslations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    use IncludesTranslations;
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->toISOString()),
            'purge_status' => $this->when($this->purge_status, $this->purge_status),
            'purge_status_label' => $this->when($this->purge_status, fn () => __('enums.catalog_purge_status.'.$this->purge_status)),
            'name' => $this->name,
            'translations' => $this->translationsWhenShowing($request),
            'description' => $this->description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'image' => $this->getMedia('image')->first()?->getUrl(),
            'active' => (bool) $this->is_active,
            'brand' => $this->whenLoaded(
                relationship: 'brand',
                value:fn() => new BrandResource($this->brand)
            ),
            'sub_brand' => $this->whenLoaded(
                relationship: 'subBrand',
                value:fn() =>new SubBrandResource($this->subBrand)
            ),
            'category' => $this->whenLoaded(
                relationship: 'category',
                value:fn() =>new CategoryResource($this->category)
            ),
            'sub_category' => $this->whenLoaded(
                relationship: 'subCategory',
                value:fn() =>new SubCategoryResource($this->subCategory)
            ),
            'created_at' => $this->created_at
        ];
    }
}
