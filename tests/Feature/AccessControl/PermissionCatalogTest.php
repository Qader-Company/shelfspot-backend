<?php

namespace Tests\Feature\AccessControl;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\ValueObjects\PermissionGroupEnum;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PermissionCatalogTest extends TestCase
{
    public function test_grouped_permissions_use_separate_v2_routes_while_v1_routes_remain_available(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());

        foreach (['admin', 'company'] as $portal) {
            $this->assertTrue($routes->contains(fn ($route) => $route->uri() === "api/v1/{$portal}/access-control/permissions"));
            $this->assertTrue($routes->contains(fn ($route) => $route->uri() === "api/v2/{$portal}/access-control/permission-groups"));
        }
    }

    #[DataProvider('portals')]
    public function test_every_permission_belongs_to_one_translated_group(string $portal): void
    {
        foreach (['en', 'ar'] as $locale) {
            foreach (PermissionCatalog::cases($portal) as $permission) {
                $this->assertTrue(Lang::has("access_control.permissions.{$portal}.{$permission->value}", $locale));
                $this->assertTrue(Lang::has("access_control.groups.{$permission->group()->value}", $locale));
            }
        }
    }

    #[DataProvider('portals')]
    public function test_permissions_are_grouped_without_duplicates(string $portal): void
    {
        $permissions = collect(PermissionCatalog::cases($portal))
            ->map(function ($permission) use ($portal): Permission {
                $model = new Permission;
                $model->forceFill([
                    'id' => array_search($permission, PermissionCatalog::cases($portal), true) + 1,
                    'name' => $permission->value,
                    'portal' => $portal,
                ]);

                return $model;
            });

        $groups = PermissionCatalog::grouped($portal, $permissions);
        $groupedNames = $groups->pluck('permissions')->flatten()->pluck('name');

        $this->assertCount(count(PermissionCatalog::cases($portal)), $groupedNames);
        $this->assertCount($groupedNames->count(), $groupedNames->unique());
        $this->assertEqualsCanonicalizing(PermissionCatalog::names($portal), $groupedNames->all());
        $this->assertNotContains([], $groups->pluck('permissions')->map->all()->all());
    }

    public function test_all_permission_groups_have_english_and_arabic_translations(): void
    {
        foreach (PermissionGroupEnum::cases() as $group) {
            foreach (['en', 'ar'] as $locale) {
                $this->assertTrue(Lang::has("access_control.groups.{$group->value}", $locale));
            }
        }
    }

    public static function portals(): array
    {
        return [
            'admin portal' => [PermissionCatalog::ADMIN_PORTAL],
            'company portal' => [PermissionCatalog::COMPANY_PORTAL],
        ];
    }
}
