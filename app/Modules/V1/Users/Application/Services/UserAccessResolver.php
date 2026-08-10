<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class UserAccessResolver
{
    /** @return array{roles: array<int, string>, permissions: array<int, string>} */
    public static function resolve(User $user): array
    {
        $portal = match ($user->type) {
            PortalTypeEnum::ADMIN => PermissionCatalog::ADMIN_PORTAL,
            PortalTypeEnum::COMPANY => PermissionCatalog::COMPANY_PORTAL,
            default => null,
        };

        if ($portal === null) {
            return ['roles' => [], 'permissions' => []];
        }

        $companyId = $user->type === PortalTypeEnum::COMPANY
            ? $user->companyUser?->company_id
            : null;

        $roles = $user->roles()
            ->where('portal', $portal)
            ->where('company_id', $companyId)
            ->with(['permissions' => fn ($query) => $query->where('portal', $portal)])
            ->get();

        return [
            'roles' => $roles
                ->pluck('name')
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'permissions' => $roles
                ->flatMap->permissions
                ->pluck('name')
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ];
    }
}
