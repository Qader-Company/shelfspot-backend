<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@shelfspot.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'type' => PortalTypeEnum::ADMIN,
                'email_verified_at' => Carbon::now(),
            ]
        );

        $user->admin()->firstOrCreate([]);

        app(FullAccessRoleProvisioner::class)->assignSuperAdminRole($user);
    }
}
