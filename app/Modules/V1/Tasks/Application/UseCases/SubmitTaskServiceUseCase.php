<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceSubmission;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitTaskServiceUseCase
{
    public function execute(Task $task, TaskService $taskService, Worker $worker, array $formData, array $filesByField = []): TaskServiceSubmission
    {
        return DB::transaction(function () use ($task, $taskService, $worker, $formData, $filesByField) {
            /** @var Task $lockedTask */
            $lockedTask = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            /** @var TaskService $lockedTaskService */
            $lockedTaskService = TaskService::query()
                ->with(['service', 'products'])
                ->whereKey($taskService->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureWorkerCanSubmit($lockedTask, $lockedTaskService, $worker);

            /** @var TaskServiceSubmission $submission */
            $submission = TaskServiceSubmission::query()->updateOrCreate(
                ['task_service_id' => $lockedTaskService->id],
                [
                    'worker_id' => $worker->id,
                    'form_data' => $formData,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]
            );

            $this->syncSubmissionFiles($submission, $filesByField);

            $lockedTaskService->forceFill([
                'status' => TaskServiceStatusEnum::COMPLETED,
            ])->save();

            return $submission->refresh()->load(['taskService.service.translations', 'worker']);
        });
    }

    private function ensureWorkerCanSubmit(Task $task, TaskService $taskService, Worker $worker): void
    {
        if ((int) $taskService->task_id !== (int) $task->id) {
            throw ValidationException::withMessages(['task_service' => __('tasks.validation.service_not_in_task')]);
        }

        if ($task->status !== TaskStatusEnum::IN_PROGRESS) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.submit_in_progress_only')]);
        }

        if ((int) $task->assigned_worker_id !== (int) $worker->id) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.worker_not_assigned')]);
        }
    }

    private function syncSubmissionFiles(TaskServiceSubmission $submission, array $filesByField): void
    {
        foreach ($filesByField as $field => $files) {
            $submission->clearMediaCollection($field);

            foreach (Arr::wrap($files) as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $submission
                    ->addMedia($file)
                    ->withCustomProperties(['field' => $field])
                    ->toMediaCollection($field);
            }
        }
    }
}
