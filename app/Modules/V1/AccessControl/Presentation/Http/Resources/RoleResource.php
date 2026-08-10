<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'portal' => $this->portal,
            'company_id' => $this->when($this->company_id, $this->company_id),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'available_permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
