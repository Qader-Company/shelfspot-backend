<?php

namespace Database\Seeders;

use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'key' => ServiceTypeEnum::PRIMARY_DISPLAY->value,
                'translations' => [
                    'en' => [
                        'description' => 'Ensure products are displayed on shelf according to planogram, FIFO rules and pricing guidelines.',
                    ]
                ],
                'minimum_price' => 50,
                'minimum_execution_time' => 30,
                'is_active' => true,
            ],
            [
                'key' => ServiceTypeEnum::SECONDARY_DISPLAY_EXECUTION->value,
                'translations' => [
                    'en' => [
                        'description' => 'Execute secondary displays according to the approved job order and planogram.',
                    ]
                ],
                'minimum_price' => 75,
                'minimum_execution_time' => 45,
                'is_active' => true,
            ],
            [
                'key' => ServiceTypeEnum::ON_SHELF_AVAILABILITY->value,
                'translations' => [
                    'en' => [
                        'description' => 'Report product availability on shelf as Available or Unavailable.',
                    ]
                ],
                'minimum_price' => 25,
                'minimum_execution_time' => 15,
                'is_active' => true,
            ],
            [
                'key' => ServiceTypeEnum::INSTORE_VISIBILITY->value,
                'translations' => [
                    'en' => [
                        'description' => 'Capture visibility photos for primary and secondary displays.',
                    ]
                ],
                'minimum_price' => 30,
                'minimum_execution_time' => 20,
                'is_active' => true,
            ],
            [
                'key' => ServiceTypeEnum::FRESHNESS_REPORT->value,
                'translations' => [
                    'en' => [
                        'description' => 'Report product quantities and expiry dates from store inventory.',
                    ]
                ],
                'minimum_price' => 40,
                'minimum_execution_time' => 25,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            $translations = Arr::pull($service, 'translations');
            $service = Service::create($service);
            foreach ($translations as $locale => $translation) {
                $service->translateOrNew($locale)->fill($translation)->save();
            }
        }
    }
}
