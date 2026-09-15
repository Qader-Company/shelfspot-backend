<?php

namespace Database\Seeders;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Stores\Domain\Models\Store;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceProduct;
use App\Modules\V1\Tasks\Domain\Models\TaskStatusHistory;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoNearbyPendingTasksSeeder extends Seeder
{
    private const BANDS = [
        'local' => ['count' => 16, 'distance_km' => 2.5],
        '10km' => ['count' => 8, 'distance_km' => 9.5],
        '20km' => ['count' => 8, 'distance_km' => 19.5],
        '30km' => ['count' => 8, 'distance_km' => 29.5],
    ];

    public function run(): void
    {
        $company = Company::query()->where('email', 'catalog@shelfspot.test')->firstOrFail();
        $owner = User::query()->where('email', 'owner@shelfspot.test')->firstOrFail();
        $worker = Worker::query()->whereHas('user', fn ($query) => $query
            ->where('email', 'adel.elmashhoor@shelfspot.test'))->firstOrFail();
        $service = Service::query()->where('is_active', true)->firstOrFail();
        $products = Product::query()
            ->where('company_id', $company->id)
            ->whereIn('sku', ['DEMO-BEV-001', 'DEMO-BEV-002', 'DEMO-BEV-003'])
            ->orderBy('sku')
            ->get();

        if ($worker->last_latitude === null || $worker->last_longitude === null || $products->isEmpty()) {
            throw new \RuntimeException('The demo worker location and catalog products must be seeded first.');
        }

        $today = now()->toDateString();

        DB::transaction(function () use ($company, $owner, $worker, $service, $products, $today): void {
            foreach (self::BANDS as $band => $config) {
                $prefix = "[Demo nearby pending: {$band}:";
                for ($slot = 0; $slot < $config['count']; $slot++) {
                    $bearing = fmod($slot * 137.507764, 360.0);
                    [$latitude, $longitude] = $this->coordinatesAtDistance(
                        (float) $worker->last_latitude,
                        (float) $worker->last_longitude,
                        $config['distance_km'],
                        $bearing,
                    );

                    $store = Store::query()->updateOrCreate(
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

                    // Link tasks seeded before stores were available without changing their lifecycle.
                    Task::query()
                        ->where('company_id', $company->id)
                        ->whereDate('date', $today)
                        ->where('notes', 'like', $prefix.'%')
                        ->where('location_name', $store->name)
                        ->whereNull('store_id')
                        ->update([
                            'store_id' => $store->id,
                            'store_number' => $store->number,
                        ]);

                    $hasPendingTask = Task::query()
                        ->where('company_id', $company->id)
                        ->whereDate('date', $today)
                        ->where('status', TaskStatusEnum::PENDING)
                        ->where('payment_status', TaskPaymentStatusEnum::CHARGED)
                        ->whereNull('assigned_worker_id')
                        ->where('notes', 'like', $prefix.'%')
                        ->where('store_id', $store->id)
                        ->exists();

                    if ($hasPendingTask) {
                        continue;
                    }

                    $task = Task::query()->create([
                        'company_id' => $company->id,
                        'store_id' => $store->id,
                        'date' => $today,
                        'execution_time' => '00:00:00',
                        'estimated_duration_minutes' => 60,
                        'latitude' => $store->latitude,
                        'longitude' => $store->longitude,
                        'location_name' => $store->name,
                        'address' => $store->address,
                        'store_number' => $store->number,
                        'total_price' => $service->price,
                        'notes' => $prefix.' '.Str::uuid().']',
                        'status' => TaskStatusEnum::PENDING,
                        'created_by' => $owner->id,
                        'payment_status' => TaskPaymentStatusEnum::CHARGED,
                        'charged_at' => now(),
                        'expires_at' => now()->addDay(),
                    ]);

                    $taskService = TaskService::query()->create([
                        'task_id' => $task->id,
                        'service_id' => $service->id,
                        'execution_instructions' => 'Complete the demo retail service for the listed products.',
                        'unit_price' => $service->price,
                        'status' => TaskServiceStatusEnum::PENDING,
                        'sort_order' => 0,
                    ]);

                    foreach ($products as $product) {
                        TaskServiceProduct::query()->create([
                            'task_service_id' => $taskService->id,
                            'product_id' => $product->id,
                            'product_details' => ['demo_nearby_band' => $band],
                        ]);
                    }

                    TaskStatusHistory::query()->create([
                        'task_id' => $task->id,
                        'from_status' => TaskStatusEnum::DRAFT->value,
                        'to_status' => TaskStatusEnum::PENDING->value,
                    ]);
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
