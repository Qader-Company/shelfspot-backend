<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;

use App\Modules\V1\Services\Presentation\Http\Resources\ServiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'execution_instructions' => $this->execution_instructions,
            'unit_price' => $this->unit_price,
            'status' => $this->status->value,
            'sort_order' => $this->sort_order,
            'service' => $this->whenLoaded('service', fn () => new ServiceResource($this->service)),
            'products' => TaskServiceProductResource::collection($this->whenLoaded('products')),
            'attachments' => $this->getMedia()->map(fn ($media) => [
                'id' => $media->id,
                'field' => $media->getCustomProperty('field'),
                'collection' => $media->collection_name,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
            ]),
        ];
    }
}
