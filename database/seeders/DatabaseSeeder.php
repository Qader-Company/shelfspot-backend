<?php

namespace Database\Seeders;

use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ServiceSeeder::class,
            AccessControlPermissionSeeder::class,
            AdminSeeder::class,
            DemoCompanyCatalogSeeder::class,
        ]);
    }
}
