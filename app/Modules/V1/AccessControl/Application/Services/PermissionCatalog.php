<?php

namespace App\Modules\V1\AccessControl\Application\Services;

use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\PermissionGroupEnum;
use Illuminate\Support\Collection;
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

    public static function group(string $portal, string $permission): PermissionGroupEnum
    {
        foreach (self::cases($portal) as $case) {
            if ($case->value === $permission) {
                return $case->group();
            }
        }

        throw new InvalidArgumentException("Unsupported permission [{$permission}] for portal [{$portal}].");
    }

    public static function grouped(string $portal, Collection $permissions): Collection
    {
        $permissionsByName = $permissions->keyBy('name');

        return collect(self::cases($portal))
            ->groupBy(fn ($permission) => $permission->group()->value)
            ->map(function (Collection $cases) use ($permissionsByName): array {
                $group = $cases->first()->group();

                return [
                    'key' => $group->value,
                    'label' => $group->label(),
                    'permissions' => $cases
                        ->map(fn ($permission) => $permissionsByName->get($permission->value))
                        ->filter()
                        ->values(),
                ];
            })
            ->values();
    }

    public static function sync(string $portal): void
    {
        foreach (self::cases($portal) as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
                'portal' => $portal,
            ]);
        }
    }
}
