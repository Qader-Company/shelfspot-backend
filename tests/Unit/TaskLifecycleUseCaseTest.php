<?php

namespace Tests\Unit;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\Tasks\Application\UseCases\AcceptTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\DeleteCompanyTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\StartTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskLifecycleUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_worker_accepts_pending_charged_task_on_execution_date(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();

        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        $this->assertSame(TaskStatusEnum::ACCEPTED, $acceptedTask->status);
        $this->assertSame($worker->id, $acceptedTask->assigned_worker_id);
        $this->assertTrue($acceptedTask->accepted_at->equalTo(now()));
        $this->assertTrue($acceptedTask->start_deadline_at->equalTo(now()->addMinutes(AcceptTaskUseCase::START_DEADLINE_MINUTES)));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::PENDING->value,
            'to_status' => TaskStatusEnum::ACCEPTED->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    public function test_worker_cannot_accept_task_outside_execution_date(): void
    {
        Carbon::setTestNow('2026-06-09 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker(['date' => '2026-06-10']);

        $this->expectException(ValidationException::class);

        app(AcceptTaskUseCase::class)->execute($task, $worker);
    }

    public function test_worker_starts_accepted_task_inside_geofence(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        $startedTask = app(StartTaskUseCase::class)->execute($acceptedTask, $worker, 30.0444, 31.2357);

        $this->assertSame(TaskStatusEnum::IN_PROGRESS, $startedTask->status);
        $this->assertTrue($startedTask->started_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::ACCEPTED->value,
            'to_status' => TaskStatusEnum::IN_PROGRESS->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    public function test_worker_cannot_start_after_deadline(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        Carbon::setTestNow('2026-06-10 09:16:00');

        $this->expectException(ValidationException::class);

        app(StartTaskUseCase::class)->execute($acceptedTask, $worker, 30.0444, 31.2357);
    }

    public function test_worker_cannot_start_outside_geofence(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        $this->expectException(ValidationException::class);

        app(StartTaskUseCase::class)->execute($acceptedTask, $worker, 31.2001, 29.9187);
    }


    public function test_company_delete_hides_task_without_changing_operational_status(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        $deletedTask = app(DeleteCompanyTaskUseCase::class)->execute($acceptedTask, $worker->user);

        $this->assertSame(TaskStatusEnum::ACCEPTED, $deletedTask->status);
        $this->assertTrue($deletedTask->company_deleted_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::ACCEPTED->value,
            'to_status' => TaskStatusEnum::COMPANY_DELETED->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    private function pendingTaskAndWorker(array $taskOverrides = []): array
    {
        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->numerify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $worker = Worker::query()->create([
            'user_id' => $user->id,
            'phone' => fake()->unique()->numerify('011########'),
            'is_active' => true,
        ]);

        $task = Task::query()->create(array_merge([
            'company_id' => $company->id,
            'date' => '2026-06-10',
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'subtotal' => 100,
            'total_price' => 100,
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
            'charged_at' => now(),
        ], $taskOverrides));

        return [$task, $worker];
    }
}
