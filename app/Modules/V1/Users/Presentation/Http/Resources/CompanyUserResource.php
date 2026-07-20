<?php

namespace App\Modules\V1\Users\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyUserResource extends JsonResource
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
            'company_id' => $this->companyUser->company_id,
            'is_owner' => (bool) $this->companyUser->is_owner,
            'is_active' => (bool) $this->companyUser->is_active,
        ];
    }
}
