<?php

namespace App\Modules\V1\Companies\Presentation\Http\Resources;

use App\Modules\V1\Companies\Presentation\Http\Resources\CompanyUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'name' => $this->name ,
            'slug' => $this->slug ,
            'email' => $this->email ,
            'phone' => $this->phone ,
            'cr_number' => $this->cr_number ,
            'industry' => $this->industry->label() ,
            'is_active' => $this->is_active,
            'users' => CompanyUserResource::collection($this->whenLoaded('companyUsers')),
        ];
    }
}
