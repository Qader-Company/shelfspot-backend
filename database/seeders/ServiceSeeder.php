<?php

namespace Database\Seeders;

use App\Modules\V1\Services\Domain\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('shelfspot_services.catalog', []) as $key => $serviceConfig) {
            $service = Service::updateOrCreate(
                ['key' => $key],
                [
                    'minimum_price' => $serviceConfig['minimum_price'],
                    'minimum_execution_time' => $serviceConfig['minimum_execution_time'],
                    'is_active' => true,
                ]
            );

            foreach ($serviceConfig['description'] as $locale => $description) {
                $service->translateOrNew($locale)->fill([
                    'description' => $description,
                ])->save();
            }
        }
    }
}
