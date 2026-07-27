<?php

namespace Tests\Unit;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Tasks\Application\Services\TaskNotificationDispatcher;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Notifications\RealtimeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaskNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_publish_notifies_an_available_nearby_worker_only(): void
    {
        Notification::fake();

        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => 'acme@example.test',
            'phone' => '01000000000',
            'cr_number' => 'CR-1000',
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
        $workerUser = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $worker = Worker::query()->create([
            'user_id' => $workerUser->id,
            'phone' => '01100000000',
            'is_active' => true,
            'last_latitude' => 30.0450,
            'last_longitude' => 31.2360,
        ]);
        $farWorkerUser = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        Worker::query()->create([
            'user_id' => $farWorkerUser->id,
            'phone' => '01100000001',
            'is_active' => true,
            'last_latitude' => 30.2000,
            'last_longitude' => 31.5000,
        ]);
        $task = Task::query()->create([
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 100,
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
        ]);

        app(TaskNotificationDispatcher::class)->statusChanged(
            task: $task,
            fromStatus: TaskStatusEnum::DRAFT,
            toStatus: TaskStatusEnum::PENDING,
            actor: null,
            meta: ['status_history_id' => 1],
        );

        Notification::assertSentTo(
            $workerUser,
            RealtimeNotification::class,
            fn (RealtimeNotification $notification) => $notification->toArray($workerUser)['event'] === 'task.published',
        );
        Notification::assertNotSentTo($farWorkerUser, RealtimeNotification::class);
    }

    public function test_task_publish_caps_nearby_worker_notifications_at_one_hundred_recipients(): void
    {
        Notification::fake();

        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => 'acme@example.test',
            'phone' => '01000000000',
            'cr_number' => 'CR-1000',
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);

        foreach (range(1, 101) as $index) {
            $user = User::factory()->create(['type' => PortalTypeEnum::WORKER]);

            Worker::query()->create([
                'user_id' => $user->id,
                'phone' => sprintf('011%08d', $index),
                'is_active' => true,
                'last_latitude' => 30.0444 + ($index / 100000),
                'last_longitude' => 31.2357,
            ]);
        }

        $task = Task::query()->create([
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 100,
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
        ]);

        app(TaskNotificationDispatcher::class)->statusChanged(
            task: $task,
            fromStatus: TaskStatusEnum::DRAFT,
            toStatus: TaskStatusEnum::PENDING,
            actor: null,
            meta: ['status_history_id' => 1],
        );

        Notification::assertSentTimes(RealtimeNotification::class, 100);
    }

    public function test_notification_payload_uses_the_captured_task_status_snapshot(): void
    {
        Notification::fake();

        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => 'acme@example.test',
            'phone' => '01000000000',
            'cr_number' => 'CR-1000',
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
        $workerUser = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $worker = Worker::query()->create([
            'user_id' => $workerUser->id,
            'phone' => '01100000000',
            'is_active' => true,
            'last_latitude' => 30.0450,
            'last_longitude' => 31.2360,
        ]);
        $task = Task::query()->create([
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 100,
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
        ]);
        $occurredAt = now()->subMinute()->toIso8601String();
        $snapshot = app(TaskNotificationDispatcher::class)->capture(
            task: $task,
            fromStatus: TaskStatusEnum::DRAFT,
            toStatus: TaskStatusEnum::PENDING,
            actor: null,
            statusHistoryId: 99,
            meta: [],
            occurredAt: $occurredAt,
        );

        $task->forceFill(['status' => TaskStatusEnum::FAILED])->save();
        $worker->forceFill(['is_active' => false])->save();

        app(TaskNotificationDispatcher::class)->dispatch($snapshot);

        Notification::assertSentTo(
            $workerUser,
            RealtimeNotification::class,
            fn (RealtimeNotification $notification) => $notification->toArray($workerUser)['status'] === TaskStatusEnum::PENDING->value
                && $notification->toArray($workerUser)['occurred_at'] === $occurredAt
                && $notification->toArray($workerUser)['meta']['status_history_id'] === 99,
        );
    }

    public function test_company_task_notifications_require_an_active_membership_with_view_task_permission(): void
    {
        Notification::fake();

        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => 'acme@example.test',
            'phone' => '01000000000',
            'cr_number' => 'CR-1000',
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
        $otherCompany = Company::query()->create([
            'name' => 'Other Acme',
            'email' => 'other-acme@example.test',
            'phone' => '01000000001',
            'cr_number' => 'CR-1001',
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
        $allowedUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        $withoutPermissionUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        $inactiveUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        $wrongCompanyRoleUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);

        CompanyUser::query()->create(['company_id' => $company->id, 'user_id' => $allowedUser->id, 'is_active' => true]);
        CompanyUser::query()->create(['company_id' => $company->id, 'user_id' => $withoutPermissionUser->id, 'is_active' => true]);
        CompanyUser::query()->create(['company_id' => $company->id, 'user_id' => $inactiveUser->id, 'is_active' => false]);
        CompanyUser::query()->create(['company_id' => $company->id, 'user_id' => $wrongCompanyRoleUser->id, 'is_active' => true]);

        $permission = Permission::query()->firstOrCreate([
            'name' => CompanyPermissionEnum::VIEW_TASK->value,
            'guard_name' => 'web',
            'portal' => PermissionCatalog::COMPANY_PORTAL,
        ]);
        $role = Role::query()->firstOrCreate([
            'name' => 'task_notification_viewer',
            'guard_name' => 'web',
            'portal' => PermissionCatalog::COMPANY_PORTAL,
            'company_id' => $company->id,
        ]);
        $role->syncPermissions([$permission]);
        $allowedUser->assignRole($role);
        $otherCompanyRole = Role::query()->firstOrCreate([
            'name' => 'other_company_task_notification_viewer',
            'guard_name' => 'web',
            'portal' => PermissionCatalog::COMPANY_PORTAL,
            'company_id' => $otherCompany->id,
        ]);
        $otherCompanyRole->syncPermissions([$permission]);
        $wrongCompanyRoleUser->assignRole($otherCompanyRole);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $task = Task::query()->create([
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 100,
            'status' => TaskStatusEnum::COMPLETED,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
        ]);

        app(TaskNotificationDispatcher::class)->statusChanged(
            task: $task,
            fromStatus: TaskStatusEnum::IN_PROGRESS,
            toStatus: TaskStatusEnum::COMPLETED,
            actor: null,
            meta: ['status_history_id' => 1],
        );

        Notification::assertSentTo($allowedUser, RealtimeNotification::class);
        Notification::assertNotSentTo($withoutPermissionUser, RealtimeNotification::class);
        Notification::assertNotSentTo($inactiveUser, RealtimeNotification::class);
        Notification::assertNotSentTo($wrongCompanyRoleUser, RealtimeNotification::class);
    }

    public function test_admin_task_notifications_require_an_active_admin_with_reassign_task_permission(): void
    {
        Notification::fake();

        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => 'acme@example.test',
            'phone' => '01000000000',
            'cr_number' => 'CR-1000',
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
        $allowedUser = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        $withoutPermissionUser = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        $inactiveUser = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);

        ShelfSpotAdmin::query()->create(['user_id' => $allowedUser->id, 'is_active' => true]);
        ShelfSpotAdmin::query()->create(['user_id' => $withoutPermissionUser->id, 'is_active' => true]);
        ShelfSpotAdmin::query()->create(['user_id' => $inactiveUser->id, 'is_active' => false]);

        $permission = Permission::query()->firstOrCreate([
            'name' => AdminPermissionEnum::REASSIGN_TASK->value,
            'guard_name' => 'web',
            'portal' => PermissionCatalog::ADMIN_PORTAL,
        ]);
        $role = Role::query()->firstOrCreate([
            'name' => 'task_notification_admin',
            'guard_name' => 'web',
            'portal' => PermissionCatalog::ADMIN_PORTAL,
            'company_id' => null,
        ]);
        $role->syncPermissions([$permission]);
        $allowedUser->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $task = Task::query()->create([
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 100,
            'status' => TaskStatusEnum::WORKER_CANCELLED,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
        ]);

        app(TaskNotificationDispatcher::class)->statusChanged(
            task: $task,
            fromStatus: TaskStatusEnum::IN_PROGRESS,
            toStatus: TaskStatusEnum::WORKER_CANCELLED,
            actor: null,
            meta: ['status_history_id' => 1],
        );

        Notification::assertSentTo($allowedUser, RealtimeNotification::class);
        Notification::assertNotSentTo($withoutPermissionUser, RealtimeNotification::class);
        Notification::assertNotSentTo($inactiveUser, RealtimeNotification::class);
    }
}
