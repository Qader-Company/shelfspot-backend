<?php

namespace Tests\Unit;

use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceSubmission;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskListResource;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskServiceResource;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskServiceSubmissionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskResourceLoadingTest extends TestCase
{
    public function test_task_service_resources_do_not_lazy_load_attachments(): void
    {
        $taskService = new TaskService([
            'id' => 1,
            'status' => TaskServiceStatusEnum::PENDING,
        ]);
        $taskService->exists = true;

        $submission = new TaskServiceSubmission([
            'id' => 1,
            'task_service_id' => 1,
            'status' => 'pending',
        ]);
        $submission->exists = true;

        DB::flushQueryLog();
        DB::enableQueryLog();

        $serviceData = (new TaskServiceResource($taskService))->resolve(new Request);
        $submissionData = (new TaskServiceSubmissionResource($submission))->resolve(new Request);

        $this->assertArrayNotHasKey('attachments', $serviceData);
        $this->assertArrayNotHasKey('attachments', $submissionData);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_task_list_resource_excludes_detail_relations(): void
    {
        $task = new \App\Modules\V1\Tasks\Domain\Models\Task([
            'id' => 1,
            'company_id' => 2,
            'date' => now()->toDateString(),
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'status' => 'pending',
            'payment_status' => 'charged',
            'total_price' => 150.5,
        ]);
        $task->exists = true;
        $task->setAttribute('services_count', 3);

        $data = (new TaskListResource($task))->resolve(new Request);

        $this->assertSame(3, $data['services_count']);
        $this->assertArrayNotHasKey('services', $data);
        $this->assertArrayNotHasKey('progress', $data);
    }
}
