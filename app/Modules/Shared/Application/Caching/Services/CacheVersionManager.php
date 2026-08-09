<?php

namespace App\Modules\Shared\Application\Caching\Services;

use App\Modules\Shared\Application\Caching\Contracts\AfterCommitDispatcherInterface;
use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;

final readonly class CacheVersionManager
{
    public function __construct(
        private AfterCommitDispatcherInterface $afterCommitDispatcher,
        private CacheStoreInterface $cacheStore,
    ) {}

    public function current(string $key): int
    {
        return (int) $this->cacheStore->rememberForever($key, fn (): int => 1);
    }

    public function incrementAfterCommit(string ...$keys): void
    {
        $keys = array_values(array_unique(array_filter($keys)));

        if ($keys === []) {
            return;
        }

        $this->afterCommitDispatcher->dispatch(function () use ($keys): void {
            foreach ($keys as $key) {
                $this->cacheStore->increment($key);
            }
        });
    }
}
