<?php

namespace Tests\Feature;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\Reports\Application\Caching\CompanyDashboardCache;
use App\Modules\V1\Reports\Application\Services\CompanyDashboardReportService;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class CompanyDashboardCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-08-02 12:00:00');
        config()->set('shelfspot_cache.enabled', true);
        config()->set('shelfspot_cache.groups.reports', true);
        config()->set('shelfspot_cache.store', 'array');
        Cache::store('array')->flush();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Cache::store('array')->flush();

        parent::tearDown();
    }

    public function test_it_reuses_a_company_dashboard_cache_entry_without_rerunning_queries(): void
    {
        $company = $this->company('Cached company');
        $this->task($company, TaskStatusEnum::PENDING);
        $service = app(CompanyDashboardReportService::class);

        DB::enableQueryLog();
        $dashboard = $service->dashboard($company->id, 'week');
        $this->assertSame(1, $dashboard['cards']['active_requests']['value']);
        $this->assertNotEmpty(DB::getQueryLog());

        DB::flushQueryLog();
        $service->dashboard($company->id, 'week');

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_a_task_change_invalidates_only_its_company_dashboard_cache(): void
    {
        $companyA = $this->company('Company A');
        $companyB = $this->company('Company B');
        $service = app(CompanyDashboardReportService::class);
        $companyAKey = CompanyDashboardCache::key($companyA->id, 'week', 'en');
        $companyBKey = CompanyDashboardCache::key($companyB->id, 'week', 'en');

        $service->dashboard($companyA->id, 'week');
        $service->dashboard($companyB->id, 'week');

        $this->assertTrue(Cache::store('array')->has($companyAKey));
        $this->assertTrue(Cache::store('array')->has($companyBKey));

        $this->task($companyA, TaskStatusEnum::PENDING);

        $this->assertFalse(Cache::store('array')->has($companyAKey));
        $this->assertTrue(Cache::store('array')->has($companyBKey));
    }

    public function test_a_rolled_back_task_change_does_not_invalidate_the_company_dashboard_cache(): void
    {
        $company = $this->company('Rollback company');
        $service = app(CompanyDashboardReportService::class);
        $cacheKey = CompanyDashboardCache::key($company->id, 'week', 'en');

        $service->dashboard($company->id, 'week');
        $this->assertTrue(Cache::store('array')->has($cacheKey));

        try {
            DB::transaction(function () use ($company): void {
                $this->task($company, TaskStatusEnum::PENDING);

                throw new RuntimeException('Rollback the task write.');
            });
        } catch (RuntimeException) {
            // The transaction is intentionally rolled back.
        }

        $this->assertTrue(Cache::store('array')->has($cacheKey));
    }

    private function company(string $name): Company
    {
        return Company::query()->create([
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->bothify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
    }

    private function task(Company $company, TaskStatusEnum $status): Task
    {
        return Task::query()->create([
            'company_id' => $company->id,
            'date' => CarbonImmutable::now()->toDateString(),
            'execution_time' => '09:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 100,
            'status' => $status,
        ]);
    }
}
