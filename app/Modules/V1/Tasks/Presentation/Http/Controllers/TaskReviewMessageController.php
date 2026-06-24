<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskReviewMessage;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Presentation\Http\Requests\TaskReviewMessageRequest;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskReviewMessageResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskReviewMessageController extends Controller
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function indexForCompany(int $id)
    {
        $task = $this->companyTask($id);
        $this->ensureCanViewMessages($task);

        return ApiResponse::success(
            TaskReviewMessageResource::collection($task->reviewMessages()->with('sender')->oldest()->get())
                ->response()
                ->getData(true)
        );
    }

    public function storeForCompany(int $id, TaskReviewMessageRequest $request)
    {
        $task = $this->companyTask($id);
        $this->ensureCanWriteMessages($task);

        $message = $this->createMessage($task, $request, 'company');

        return ApiResponse::created(new TaskReviewMessageResource($message->load('sender')));
    }

    public function indexForAdmin(int $id)
    {
        $task = $this->task($id);
        $this->ensureCanViewMessages($task);

        $messages = $task->reviewMessages()->with('sender')->oldest()->cursorPaginate();
        return ApiResponse::success(
            TaskReviewMessageResource::collection($messages)
                ->response()
                ->getData(true)
        );
    }

    public function storeForAdmin(int $id, TaskReviewMessageRequest $request)
    {
        $task = $this->task($id);
        $this->ensureCanWriteMessages($task);

        $message = $this->createMessage($task, $request, 'admin');

        return ApiResponse::created(new TaskReviewMessageResource($message->load('sender')));
    }

    private function createMessage(Task $task, TaskReviewMessageRequest $request, string $senderRole): TaskReviewMessage
    {
        return TaskReviewMessage::query()->create([
            'task_id' => $task->id,
            'sender_id' => $request->user()?->id,
            'sender_role' => $senderRole,
            'message' => $request->validated('message'),
        ]);
    }

    private function ensureCanViewMessages(Task $task): void
    {
        if (! in_array($task->status, [TaskStatusEnum::REJECTED, TaskStatusEnum::REOPENED, TaskStatusEnum::ACCEPTED], true)) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.review_messages_unavailable')]);
        }
    }

    private function ensureCanWriteMessages(Task $task): void
    {
        if ($task->status !== TaskStatusEnum::REJECTED) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.review_messages_rejected_only')]);
        }
    }

    private function companyTask(int $id): Task
    {
        $task = $this->task($id);

        if ($task->company_id !== $this->tenantContext->getCompanyId()) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $task;
    }

    private function task(int $id): Task
    {
        $task = $this->taskRepository->getById($id);

        if (! $task) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $task;
    }
}
