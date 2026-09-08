<?php

namespace Tests\Feature\AccessControl;

use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories\EloquentManagedAdminRepository;
use App\Modules\V1\Companies\Domain\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CreateCompanyAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_is_created_with_a_verified_email(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'company@example.com',
            'phone' => '0500000000',
            'cr_number' => '1234567890',
            'industry' => 'industry_one',
        ]);

        $accessControlRepository = Mockery::mock(AccessControlRepositoryInterface::class);
        $accessControlRepository
            ->shouldReceive('scopedRolesByNames')
            ->once()
            ->andReturn(new Collection());

        $repository = new EloquentManagedAdminRepository($accessControlRepository);

        $admin = $repository->createCompanyAdmin($company->id, [
            'name' => 'Company Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->email_verified_at->isToday());
    }
}
