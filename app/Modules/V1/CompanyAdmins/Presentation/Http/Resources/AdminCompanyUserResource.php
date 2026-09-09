<?php

namespace App\Modules\V1\CompanyAdmins\Presentation\Http\Resources;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCompanyUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyUser?->company_id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => (bool) $this->companyUser?->is_active,
            'is_owner' => (bool) $this->companyUser?->is_owner,
            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles
                    ->where('portal', PermissionCatalog::COMPANY_PORTAL)
                    ->where('company_id', $this->companyUser?->company_id)
                    ->pluck('name')
                    ->values(),
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
