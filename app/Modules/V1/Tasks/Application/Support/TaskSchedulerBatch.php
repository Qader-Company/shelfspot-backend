<?php

namespace App\Modules\V1\Tasks\Application\Support;

final class TaskSchedulerBatch
{
    public const CHUNK_SIZE = 100;

    public const DEFAULT_LIMIT = 500;

    public static function limit(?int $limit): int
    {
        return max(1, $limit ?? self::DEFAULT_LIMIT);
    }
}
