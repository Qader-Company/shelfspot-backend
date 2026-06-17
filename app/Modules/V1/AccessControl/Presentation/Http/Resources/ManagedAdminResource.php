<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagedAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => (bool) ($this->admin?->is_active ?? $this->companyUser?->is_active),
            'is_owner' => $this->when($this->companyUser, $this->companyUser?->is_owner),
            'roles' => $this->roles->pluck('name')->values(),
        ];
    }
}
