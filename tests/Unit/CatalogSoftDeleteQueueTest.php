<?php

namespace Tests\Unit;

use App\Modules\Shared\Application\Jobs\SoftDeleteCatalogItemJob;
use App\Modules\Shared\Infrastructure\Persistence\Repositories\CascadesCatalogTrashActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogSoftDeleteQueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('catalog_soft_delete_test_parents', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('catalog_soft_delete_test_children', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id');
            $table->string('name');
            $table->string('deleted_by_catalog_parent_type')->nullable();
            $table->unsignedBigInteger('deleted_by_catalog_parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('catalog_soft_delete_test_children');
        Schema::dropIfExists('catalog_soft_delete_test_parents');

        parent::tearDown();
    }

    public function test_catalog_soft_delete_is_queued(): void
    {
        Queue::fake();

        $parent = CatalogSoftDeleteTestParent::create(['name' => 'parent']);
        $repository = new CatalogSoftDeleteTestRepository();

        $repository->delete($parent);

        $this->assertNotSoftDeleted($parent);
        Queue::assertPushed(SoftDeleteCatalogItemJob::class);
    }

    public function test_catalog_soft_delete_job_soft_deletes_parent_and_children(): void
    {
        $parent = CatalogSoftDeleteTestParent::create(['name' => 'parent']);
        $child = $parent->children()->create(['name' => 'child']);

        (new SoftDeleteCatalogItemJob(CatalogSoftDeleteTestParent::class, $parent->id, ['children']))->handle();

        $this->assertSoftDeleted('catalog_soft_delete_test_parents', ['id' => $parent->id]);
        $this->assertSoftDeleted('catalog_soft_delete_test_children', ['id' => $child->id]);
        $this->assertDatabaseHas('catalog_soft_delete_test_children', [
            'id' => $child->id,
            'deleted_by_catalog_parent_type' => (new CatalogSoftDeleteTestParent())->getMorphClass(),
            'deleted_by_catalog_parent_id' => $parent->id,
        ]);
    }
}

class CatalogSoftDeleteTestRepository
{
    use CascadesCatalogTrashActions;

    public function delete(CatalogSoftDeleteTestParent $parent): void
    {
        $this->deleteWithCatalogChildren($parent);
    }

    protected function trashableModel(): string
    {
        return CatalogSoftDeleteTestParent::class;
    }

    protected function trashCascadeRelations(): array
    {
        return ['children'];
    }
}

class CatalogSoftDeleteTestParent extends Model
{
    use SoftDeletes;

    protected $table = 'catalog_soft_delete_test_parents';

    protected $guarded = [];

    public function children(): HasMany
    {
        return $this->hasMany(CatalogSoftDeleteTestChild::class, 'parent_id');
    }
}

class CatalogSoftDeleteTestChild extends Model
{
    use SoftDeletes;

    protected $table = 'catalog_soft_delete_test_children';

    protected $guarded = [];
}
