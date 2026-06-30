<?php

namespace App\Modules\Shared\Domain\ValueObjects;

final class CatalogPurgeStatus
{
    public const QUEUED = 'queued';
    public const FAILED = 'failed';

    public static function blocksRestore(?string $status): bool
    {
        return $status === self::QUEUED;
    }

    public static function canQueue(?string $status): bool
    {
        return $status === null || $status === self::FAILED;
    }
}
