<?php

namespace App\Modules\V1\AccessControl\Application\Services;

use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class UserPermissionLoader
{
    public static function load(User $user, PortalTypeEnum $portalType): void
    {
        if ($portalType === PortalTypeEnum::WORKER) {
            return;
        }

        $portal = $portalType->value;
        PermissionCatalog::sync($portal);

        $roles = $user->roles()
            ->where('portal', $portal)
            ->when(
                $portalType === PortalTypeEnum::COMPANY,
                fn ($query) => $query->where('company_id', $user->companyUser->company_id),
                fn ($query) => $query->whereNull('company_id')
            )
            ->with('permissions')
            ->get();

        $permissions = Permission::query()->where('portal', $portal)->get();
        $assignedPermissionIds = $roles
            ->flatMap(fn ($role) => $role->permissions->pluck('id'))
            ->unique()
            ->values()
            ->all();

        $user->setRelation(
            'assignedPermissions',
            $permissions->whereIn('id', $assignedPermissionIds)->values()
        );
        $user->setRelation(
            'availablePermissions',
            $permissions->whereNotIn('id', $assignedPermissionIds)->values()
        );
    }
}
