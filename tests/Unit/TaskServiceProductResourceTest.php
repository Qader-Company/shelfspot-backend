<?php

namespace Tests\Unit;

use App\Modules\V1\Tasks\Domain\Models\TaskServiceProduct;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskServiceProductResource;
use Tests\TestCase;

class TaskServiceProductResourceTest extends TestCase
{
    public function test_exposes_product_details_to_the_worker_task_view(): void
    {
        $taskServiceProduct = new TaskServiceProduct([
            'product_details' => ['minimum_quantity' => 5],
        ]);

        $payload = (new TaskServiceProductResource($taskServiceProduct))->resolve();

        $this->assertSame(['minimum_quantity' => 5], $payload['product_details']);
    }
}
