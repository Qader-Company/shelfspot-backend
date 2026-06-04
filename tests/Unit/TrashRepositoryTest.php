<?php

namespace Tests\Unit;

use App\Modules\Shared\Infrastructure\Persistence\Repositories\HandlesTrash;
use App\Modules\Shared\Support\Traits\DeletesMediaOnForceDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrashRepositoryTest extends TestCase
{
    private TrashTestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('trash_test_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trash_test_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trash_test_item_id');
            $table->timestamps();
        });

        $this->repository = new TrashTestRepository();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('trash_test_media');
        Schema::dropIfExists('trash_test_items');

        parent::tearDown();
    }

    public function test_it_manages_the_complete_trash_lifecycle(): void
    {
        $first = TrashTestItem::create(['name' => 'first']);
        $second = TrashTestItem::create(['name' => 'second']);
        $third = TrashTestItem::create(['name' => 'third']);
        $firstMedia = $first->media()->create();
        $secondMedia = $second->media()->create();

        $this->assertSame(2, $this->repository->bulkDelete([$first->id, $second->id]));
        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
        $this->assertModelExists($firstMedia);
        $this->assertModelExists($secondMedia);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $this->repository->getTrash()->pluck('id')->all());

        $this->assertTrue($this->repository->restore($first->id));
        $this->assertNotSoftDeleted($first->fresh());
        $this->assertSame(1, $this->repository->bulkRestore([$second->id, $third->id]));
        $this->assertNotSoftDeleted($second->fresh());

        $this->repository->bulkDelete([$first->id, $second->id]);
        $this->assertTrue($this->repository->forceDelete($first->id));
        $this->assertDatabaseMissing('trash_test_items', ['id' => $first->id]);
        $this->assertDatabaseMissing('trash_test_media', ['id' => $firstMedia->id]);
        $this->assertSame(1, $this->repository->bulkForceDelete([$second->id, $third->id]));
        $this->assertDatabaseMissing('trash_test_items', ['id' => $second->id]);
        $this->assertDatabaseMissing('trash_test_media', ['id' => $secondMedia->id]);
        $this->assertModelExists($third);
    }
}

class TrashTestRepository
{
    use HandlesTrash;

    protected function trashableModel(): string
    {
        return TrashTestItem::class;
    }
}

class TrashTestItem extends Model
{
    use DeletesMediaOnForceDelete, SoftDeletes;

    protected $guarded = [];

    public function media(): HasMany
    {
        return $this->hasMany(TrashTestMedia::class);
    }
}

class TrashTestMedia extends Model
{
    protected $table = 'trash_test_media';

    protected $guarded = [];
}
