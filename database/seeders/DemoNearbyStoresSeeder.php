<?php

namespace Database\Seeders;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Stores\Domain\Models\Store;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoNearbyStoresSeeder extends Seeder
{
    public const BANDS = [
        'local' => ['count' => 16, 'distance_km' => 2.5],
        '10km' => ['count' => 8, 'distance_km' => 9.5],
        '20km' => ['count' => 8, 'distance_km' => 19.5],
        '30km' => ['count' => 8, 'distance_km' => 29.5],
    ];

    public function run(): void
    {
        $company = Company::query()->where('email', 'catalog@shelfspot.test')->firstOrFail();
        $worker = Worker::query()->whereHas('user', fn ($query) => $query
            ->where('email', 'adel.elmashhoor@shelfspot.test'))->firstOrFail();

        if ($worker->last_latitude === null || $worker->last_longitude === null) {
            throw new \RuntimeException('The demo worker location must be seeded first.');
        }

        DB::transaction(function () use ($company, $worker): void {
            foreach (self::BANDS as $band => $config) {
                for ($slot = 0; $slot < $config['count']; $slot++) {
                    $bearing = fmod($slot * 137.507764, 360.0);
                    [$latitude, $longitude] = $this->coordinatesAtDistance(
                        (float) $worker->last_latitude,
                        (float) $worker->last_longitude,
                        $config['distance_km'],
                        $bearing,
                    );

                    $store = Store::query()->withTrashed()->updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'number' => sprintf('DEMO-NEARBY-%s-%03d', strtoupper($band), $slot + 1),
                        ],
                        [
                            'name' => "Demo store {$band} #".($slot + 1),
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'address' => "Demo location {$band}",
                            'is_active' => true,
                        ],
                    );

                    if ($store->trashed()) {
                        $store->restore();
                    }
                }
            }
        });
    }

    private function coordinatesAtDistance(float $latitude, float $longitude, float $distanceKm, float $bearingDegrees): array
    {
        $angularDistance = $distanceKm / 6371.0;
        $bearing = deg2rad($bearingDegrees);
        $lat1 = deg2rad($latitude);
        $lon1 = deg2rad($longitude);

        $lat2 = asin(sin($lat1) * cos($angularDistance)
            + cos($lat1) * sin($angularDistance) * cos($bearing));
        $lon2 = $lon1 + atan2(
            sin($bearing) * sin($angularDistance) * cos($lat1),
            cos($angularDistance) - sin($lat1) * sin($lat2),
        );

        return [round(rad2deg($lat2), 7), round(rad2deg($lon2), 7)];
    }
}
