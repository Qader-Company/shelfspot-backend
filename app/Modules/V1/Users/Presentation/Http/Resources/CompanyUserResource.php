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
            'name' => $this->name,
            'email' => $this->email,
            'is_owner' => (bool) $this->companyUser->is_owner,
            'is_active' => (bool) $this->companyUser->is_active
        ];
    }
}
