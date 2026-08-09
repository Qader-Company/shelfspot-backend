<?php

namespace App\Modules\Shared\Application\Caching\Data;

use InvalidArgumentException;

final readonly class CacheTtl
{
    public function __construct(
        public int $freshSeconds,
        public int $staleSeconds,
    ) {
        if ($freshSeconds < 1) {
            throw new InvalidArgumentException('The cache fresh duration must be at least one second.');
        }

        if ($staleSeconds < $freshSeconds) {
            throw new InvalidArgumentException('The cache stale duration must not be shorter than the fresh duration.');
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function toFlexibleDurations(): array
    {
        return [$this->freshSeconds, $this->staleSeconds];
    }
}
