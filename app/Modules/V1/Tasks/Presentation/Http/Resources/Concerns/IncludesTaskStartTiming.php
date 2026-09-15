<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources\Concerns;

use App\Modules\V1\Tasks\Application\Support\RemainingMinutes;
use App\Modules\V1\Tasks\Application\UseCases\StartTaskUseCase;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;

trait IncludesTaskStartTiming
{
    private function startTiming(bool $isWorker): mixed
    {
        return $this->when($this->accepted_at !== null, function () use ($isWorker): array {
            $extensionMinutes = (int) ($this->start_deadline_extension_minutes ?? 0);

            return [
                'at' => $this->accepted_at->toISOString(),
                'allowed_minutes' => StartTaskUseCase::START_DEADLINE_MINUTES + $extensionMinutes,
                'extended_by_minutes' => $extensionMinutes,
                'remaining_minutes' => $this->when(
                    $isWorker && $this->status === TaskStatusEnum::STARTED,
                    fn () => RemainingMinutes::until($this->start_deadline_at),
                ),
            ];
        });
    }
}
