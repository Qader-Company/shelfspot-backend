<?php

namespace Tests\Unit;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Infrastructure\Persistence\Repositories\EloquentTaskRepository;
use App\Modules\V1\Tasks\Presentation\Http\Requests\NearbyTaskRequest;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NearbyTaskDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_nearby_discovery_returns_only_pending_charged_unassigned_tasks_for_execution_date(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');
        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => 'acme@example.com',
            'phone' => '01000000000',
            'cr_number' => 'CR-100',
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);

        $assignedWorker = Worker::query()->create([
            'user_id' => User::factory()->create(['type' => PortalTypeEnum::WORKER])->id,
            'phone' => '01100000000',
            'is_active' => true,
        ]);

        $visible = $this->task($company, ['date' => '2026-06-10']);
        $this->task($company, ['date' => '2026-06-11']);
        $this->task($company, ['status' => TaskStatusEnum::STARTED]);
        $this->task($company, ['payment_status' => TaskPaymentStatusEnum::PENDING]);
        $this->task($company, ['assigned_worker_id' => $assignedWorker->id]);
        $this->task($company, ['company_deleted_at' => now()]);
        $this->task($company, ['latitude' => 30.084, 'longitude' => 31.275]);

        $latitude = 30.0444;
        $longitude = 31.2357;
        $radius = 5;

        $tasks = app(EloquentTaskRepository::class)->tasksByCoordinates(
            latitude: $latitude,
            longitude: $longitude,
            radiusKilometers: $radius,
            boundingBox: app(GeoDistanceCalculator::class)->boundingBox($latitude, $longitude, $radius)
        );

        $this->assertSame([$visible->id], $tasks->pluck('id')->all());
    }

    public function test_worker_can_request_a_nearby_task_radius_up_to_thirty_kilometers(): void
    {
        $request = new NearbyTaskRequest;

        $this->assertTrue(Validator::make(['radius_km' => 30], $request->rules())->passes());
        $this->assertTrue(Validator::make(['radius_km' => 30.1], $request->rules())->fails());
    }

    private function task(Company $company, array $overrides = []): Task
    {
        return Task::query()->create(array_merge([
            'company_id' => $company->id,
            'date' => '2026-06-10',
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 100,
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
            'charged_at' => now(),
        ], $overrides));
    }
}
