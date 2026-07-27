<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanSubmitTaskRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceSubmission;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SubmitTaskServiceUseCase
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository) {}

    public function execute(Task $task, TaskService $taskService, Worker $worker, array $formData, array $filesByField = []): TaskServiceSubmission
    {
        return DB::transaction(function () use ($task, $taskService, $worker, $formData, $filesByField) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);
            CanSubmitTaskRule::validate($lockedTask, $worker);

            $lockedTaskService = TaskService::query()
                ->with(['service', 'products'])
                ->whereKey($taskService->id)
                ->where('task_id', $task->id)
                ->lockForUpdate()
                ->firstOrFail();

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
