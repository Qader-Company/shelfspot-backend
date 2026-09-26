<?php

namespace App\Modules\V1\Admins\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShelfSpotAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => (bool) $this->admin?->is_active,
            'is_owner' => null,
            'roles' => $this->roles->first()?->name,
        ];
    }
}
