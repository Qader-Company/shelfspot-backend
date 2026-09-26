<?php

namespace App\Modules\V1\CompanyAdmins\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagedCompanyUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => (bool) $this->companyUser?->is_active,
            'is_owner' => $this->companyUser?->is_owner,
            'roles' => $this->roles->first()?->name,
        ];
    }
}
