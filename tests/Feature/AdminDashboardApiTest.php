<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskWorkerAssignment;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([CheckApiKey::class]);
        CarbonImmutable::setTestNow('2026-07-25 12:00:00');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Cache::flush();

        parent::tearDown();
    }

    public function test_authorized_admin_receives_aggregated_dashboard_data(): void
    {
        $this->authenticateAdmin();

        $companyA = $this->company('Alpha');
        $companyB = $this->company('Bravo');
        $inactiveCompany = $this->company('Inactive', false);
        $this->company('Deleted')->delete();

        $workerA = $this->worker('Worker A');
        $workerB = $this->worker('Worker B');
        $this->worker('Inactive worker', false);
        $this->worker('Deleted worker')->delete();

        $this->task($companyA, TaskStatusEnum::PENDING, $this->current());
        $inProgress = $this->task($companyA, TaskStatusEnum::IN_PROGRESS, $this->current()->subDays(2));
        $completed = $this->task($companyB, TaskStatusEnum::ACCEPTED, $this->current()->subDays(3));
        $rejected = $this->task($inactiveCompany, TaskStatusEnum::REJECTED, $this->current()->subDays(4));
        $this->task($companyB, TaskStatusEnum::PENDING, $this->current()->subWeeks(2));

        $this->completedAssignment($inProgress, $workerA);
        $this->completedAssignment($completed, $workerA);
        $this->completedAssignment($rejected, $workerB);

        $this->transaction($companyA, CompanyWalletTransactionTypeEnum::TASK_PAYMENT, 100, $this->current()->subDays(2));
        $this->transaction($companyA, CompanyWalletTransactionTypeEnum::TASK_REFUND, 20, $this->current()->subDay());
        $this->transaction($companyB, CompanyWalletTransactionTypeEnum::TASK_PAYMENT, 60, $this->current()->subDays(3));
        $this->transaction($companyB, CompanyWalletTransactionTypeEnum::TASK_PAYMENT, 500, $this->current()->subWeeks(2));

        $this->getJson('/api/v1/admin/dashboard?period=week')
            ->assertOk()
            ->assertJsonPath('data.period', 'week')
            ->assertJsonPath('data.cards.total_companies', 3)
            ->assertJsonPath('data.cards.active_companies', 2)
            ->assertJsonPath('data.cards.requests_today', 1)
            ->assertJsonPath('data.cards.total_freelancers', 3)
            ->assertJsonPath('data.cards.active_freelancers', 2)
            ->assertJsonPath('data.cards.net_payment_volume', 140)
            ->assertJsonPath('data.charts.status_distribution.0.status', 'pending')
            ->assertJsonPath('data.charts.status_distribution.0.total', 1)
            ->assertJsonPath('data.charts.status_distribution.1.total', 1)
            ->assertJsonPath('data.charts.status_distribution.2.total', 1)
            ->assertJsonPath('data.charts.status_distribution.3.total', 1)
            ->assertJsonPath('data.top_companies.0.company_id', $companyA->id)
            ->assertJsonPath('data.top_companies.0.requests_count', 2)
            ->assertJsonPath('data.top_companies.0.net_payment_volume', 80)
            ->assertJsonPath('data.top_freelancers.0.worker_id', $workerA->id)
            ->assertJsonPath('data.top_freelancers.0.completed_requests', 2);

    }

    public function test_dashboard_cache_is_invalidated_when_a_task_changes(): void
    {
        $this->authenticateAdmin();
        $company = $this->company('Cache company');

        $this->task($company, TaskStatusEnum::PENDING, $this->current());

        $this->getJson('/api/v1/admin/dashboard?period=week')
            ->assertJsonPath('data.cards.requests_today', 1);

        $this->task($company, TaskStatusEnum::PENDING, $this->current());

        $this->getJson('/api/v1/admin/dashboard?period=week')
            ->assertJsonPath('data.cards.requests_today', 2);
    }

    public function test_admin_without_dashboard_permission_is_forbidden(): void
    {
        $admin = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        Sanctum::actingAs($admin, [PortalTypeEnum::ADMIN->value, 'access']);

        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }

    private function authenticateAdmin(): void
    {
        $admin = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        app(FullAccessRoleProvisioner::class)->assignSuperAdminRole($admin);
        Sanctum::actingAs($admin, [PortalTypeEnum::ADMIN->value, 'access']);
    }

    private function company(string $name, bool $active = true): Company
    {
        return Company::query()->create([
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->bothify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => $active,
        ]);
    }

    private function worker(string $name, bool $active = true): Worker
    {
        return Worker::query()->create([
            'user_id' => User::factory()->create([
                'name' => $name,
                'type' => PortalTypeEnum::WORKER,
            ])->id,
            'phone' => fake()->unique()->numerify('011########'),
            'is_active' => $active,
        ]);
    }

    private function task(Company $company, TaskStatusEnum $status, CarbonImmutable $createdAt): Task
    {
        $task = Task::query()->create([
            'company_id' => $company->id,
            'date' => $createdAt->toDateString(),
            'execution_time' => '09:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 100,
            'status' => $status,
        ]);

        $task->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $task;
    }

    private function completedAssignment(Task $task, Worker $worker): void
    {
        $assignment = TaskWorkerAssignment::query()->create([
            'task_id' => $task->id,
            'worker_id' => $worker->id,
            'assignment_type' => 'initial',
            'assigned_at' => $this->current()->subHour(),
            'unassigned_at' => $this->current(),
            'outcome' => 'completed',
        ]);

        $assignment->forceFill(['updated_at' => $this->current()])->saveQuietly();
    }

    private function transaction(
        Company $company,
        CompanyWalletTransactionTypeEnum $type,
        float $amount,
        CarbonImmutable $createdAt,
    ): void {
        $transaction = CompanyWalletTransaction::query()->create([
            'company_id' => $company->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => 0,
        ]);

        $transaction->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();
    }

    private function current(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }
}
