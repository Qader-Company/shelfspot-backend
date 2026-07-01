<?php

namespace App\Modules\V1\Tasks\Domain\ValueObjects;

use App\Modules\V1\Tasks\Domain\Models\Task;
use Illuminate\Support\Carbon;

final class TaskAutoAcceptDate
{
    private const REVIEW_DAYS = 2;

    public static function fromTask(Task $task): Carbon
    {
        $executionTime = $task->execution_time instanceof Carbon
            ? $task->execution_time->format('H:i:s')
            : (string) $task->execution_time;

        return Carbon::parse($task->date->toDateString().' '.$executionTime)
            ->addMinutes((int) $task->estimated_duration_minutes)
            ->addDays(self::REVIEW_DAYS);
    }
}
