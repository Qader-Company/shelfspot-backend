<?php

namespace App\Modules\V1\AccessControl\Application\Services;

use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use InvalidArgumentException;

class PermissionCatalog
{
    public const ADMIN_PORTAL = 'admin';
    public const COMPANY_PORTAL = 'company';

    public static function cases(string $portal): array
    {
        return match ($portal) {
            self::ADMIN_PORTAL => AdminPermissionEnum::cases(),
            self::COMPANY_PORTAL => CompanyPermissionEnum::cases(),
            default => throw new InvalidArgumentException("Unsupported access-control portal [{$portal}]."),
        };
    }

    public static function names(string $portal): array
    {
        return array_map(fn ($permission) => $permission->value, self::cases($portal));
    }

    public static function options(string $portal): array
    {
        return array_map(fn ($permission) => [
            'name' => $permission->value,
            'label' => $permission->label(),
        ], self::cases($portal));
    }

    public static function label(string $portal, string $permission): string
    {
        foreach (self::cases($portal) as $case) {
            if ($case->value === $permission) {
                return $case->label();
            }
        }

        return $permission;
    }

    public static function sync(string $portal, ?int $companyId = null): void
    {
        foreach (self::cases($portal) as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
                'portal' => $portal,
                'company_id' => $companyId,
            ]);
        }
    }
}
