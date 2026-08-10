<?php

namespace App\Modules\V1\Users\Presentation\Http\Resources;

use App\Modules\V1\Users\Application\Services\UserAccessResolver;
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
        $access = UserAccessResolver::resolve($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type?->value,
            'is_active' => (bool) $this->admin->is_active,
            'roles' => $access['roles'],
            'permissions' => $access['permissions'],
        ];
    }
}
