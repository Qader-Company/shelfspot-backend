<?php

namespace App\Modules\V1\Brands\Presentation\Http\Resources;

use App\Modules\Shared\Presentation\Http\Resources\Concerns\IncludesTranslations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    use IncludesTranslations;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->toISOString()),
            'purge_status' => $this->when($this->purge_status, $this->purge_status),
            'purge_status_label' => $this->when($this->purge_status, fn () => __('enums.catalog_purge_status.'.$this->purge_status)),
            'name' => $this->name,
            'translations' => $this->translationsWhenShowing($request),
            'logo' => $this->whenLoaded(
                'media',
                $this->getMedia('logo')->first()?->getUrl() ?? '',
                ''
            ),
            'active' => (bool) $this->is_active,
            'created_at' => $this->created_at
        ];
    }
}
