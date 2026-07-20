<?php

namespace Tests\Unit;

use App\Modules\V1\Tasks\Domain\Models\TaskService;
use Tests\TestCase;

class TaskServiceMediaCollectionsTest extends TestCase
{
    public function test_task_service_registers_all_request_attachment_collections(): void
    {
        $collections = (new TaskService)
            ->getRegisteredMediaCollections()
            ->pluck('name')
            ->all();

        $this->assertSame(['planogram_files', 'job_order_files'], $collections);
    }
}
