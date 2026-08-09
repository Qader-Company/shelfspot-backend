<?php

namespace App\Modules\Shared\Application\Caching\Services;

use App\Modules\Shared\Application\Caching\Contracts\AfterCommitDispatcherInterface;
use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;

final readonly class CacheInvalidationDispatcher
{
    public function __construct(
        private AfterCommitDispatcherInterface $afterCommitDispatcher,
        private CacheStoreInterface $cacheStore,
    ) {}

    public function forgetAfterCommit(string ...$keys): void
    {
        $keys = array_values(array_unique(array_filter($keys)));

        if ($keys === []) {
            return;
        }

        $this->afterCommitDispatcher->dispatch(
            fn () => $this->cacheStore->forgetMany($keys)
        );
    }
}
