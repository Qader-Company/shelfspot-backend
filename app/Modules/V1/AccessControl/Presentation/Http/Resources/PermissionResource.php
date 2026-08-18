<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Resources;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $group = PermissionCatalog::group($this->portal, $this->name);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => PermissionCatalog::label($this->portal, $this->name),
            'portal' => $this->portal,
            'group' => $group->value,
            'group_label' => $group->label(),
        ];
    }
}
