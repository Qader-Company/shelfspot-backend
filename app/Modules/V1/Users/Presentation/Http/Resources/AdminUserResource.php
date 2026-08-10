<?php

namespace App\Modules\V1\Users\Presentation\Http\Resources;

use App\Modules\V1\AccessControl\Presentation\Http\Resources\PermissionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type?->value,
            'is_active' => (bool) $this->admin->is_active,
            'permissions' => PermissionResource::collection($this->whenLoaded('assignedPermissions')),
            'available_permissions' => PermissionResource::collection($this->whenLoaded('availablePermissions')),
        ];
    }
}
