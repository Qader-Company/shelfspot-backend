<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskWorkerAssignment;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkerMyTasksApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([CheckApiKey::class]);
    }

    public function test_worker_can_combine_status_and_reassigned_to_me_filters(): void
    {
        $company = $this->company();
        $worker = $this->worker();

        $completedTask = $this->task($company, $worker, TaskStatusEnum::COMPLETED);
        $this->assign($completedTask, $worker, TaskWorkerAssignmentTypeEnum::INITIAL);

        $reassignedTask = $this->task($company, $worker, TaskStatusEnum::STARTED);
        $this->assign($reassignedTask, $worker, TaskWorkerAssignmentTypeEnum::REASSIGNED);

        $reopenedReassignedTask = $this->task($company, $worker, TaskStatusEnum::REOPENED);
        $this->assign($reopenedReassignedTask, $worker, TaskWorkerAssignmentTypeEnum::REOPENED_REASSIGNED);

        $acceptedReassignedTask = $this->task($company, $worker, TaskStatusEnum::ACCEPTED);
        $this->assign($acceptedReassignedTask, $worker, TaskWorkerAssignmentTypeEnum::REASSIGNED, now());

        Sanctum::actingAs($worker->user, [PortalTypeEnum::WORKER->value, 'access']);

        $response = $this->getJson('/api/v1/worker/tasks/my?reassigned_to_me=true');

        $response->assertOk();
        $tasks = collect($response->json('data.data'));

        $this->assertEqualsCanonicalizing(
            [$reassignedTask->id, $reopenedReassignedTask->id, $acceptedReassignedTask->id],
            $tasks->pluck('id')->all(),
        );
        $this->assertSame(
            TaskWorkerAssignmentTypeEnum::REASSIGNED->value,
            $tasks->firstWhere('id', $reassignedTask->id)['assignment_type'],
        );
        $this->assertSame(
            TaskWorkerAssignmentTypeEnum::REOPENED_REASSIGNED->value,
            $tasks->firstWhere('id', $reopenedReassignedTask->id)['assignment_type'],
        );

        $this->getJson('/api/v1/worker/tasks/my?status=started&reassigned_to_me=true')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $reassignedTask->id)
            ->assertJsonCount(1, 'data.data');

        $this->getJson('/api/v1/worker/tasks/my?status=accepted&reassigned_to_me=true')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $acceptedReassignedTask->id)
            ->assertJsonCount(1, 'data.data');
    }

    private function company(): Company
    {
        return Company::query()->create([
            'name' => 'Acme',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->numerify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
    }

    private function worker(): Worker
    {
        return Worker::query()->create([
            'user_id' => User::factory()->create(['type' => PortalTypeEnum::WORKER])->id,
            'phone' => fake()->unique()->numerify('011########'),
            'is_active' => true,
        ]);
    }

    private function task(Company $company, Worker $worker, TaskStatusEnum $status): Task
    {
        return Task::query()->create([
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'location_name' => 'Store branch',
            'total_price' => 100,
            'status' => $status,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
            'assigned_worker_id' => $worker->id,
            'charged_at' => now(),
        ]);
    }

    private function assign(
        Task $task,
        Worker $worker,
        TaskWorkerAssignmentTypeEnum $type,
        mixed $unassignedAt = null,
    ): void {
        TaskWorkerAssignment::query()->create([
            'task_id' => $task->id,
            'worker_id' => $worker->id,
            'assignment_type' => $type,
            'assigned_at' => now(),
            'unassigned_at' => $unassignedAt,
        ]);
    }
}
