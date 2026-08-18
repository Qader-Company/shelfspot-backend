<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->resource['key'],
            'label' => $this->resource['label'],
            'permissions' => PermissionResource::collection($this->resource['permissions']),
        ];
    }
}
