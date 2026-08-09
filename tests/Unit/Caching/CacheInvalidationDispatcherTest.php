<?php

namespace Tests\Unit\Caching;

use App\Modules\Shared\Application\Caching\Contracts\AfterCommitDispatcherInterface;
use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;
use App\Modules\Shared\Application\Caching\Data\CacheTtl;
use App\Modules\Shared\Application\Caching\Services\CacheInvalidationDispatcher;
use Closure;
use Tests\TestCase;

class CacheInvalidationDispatcherTest extends TestCase
{
    public function test_it_forgets_unique_keys_only_after_the_commit_callback_runs(): void
    {
        $afterCommit = new DeferredAfterCommitDispatcher;
        $cacheStore = new RecordingCacheStore;
        $dispatcher = new CacheInvalidationDispatcher($afterCommit, $cacheStore);

        $dispatcher->forgetAfterCommit('reports:admin:week', '', 'reports:admin:week', 'reports:admin:month');

        $this->assertSame([], $cacheStore->forgottenKeySets);

        $afterCommit->runCallbacks();

        $this->assertSame([
            ['reports:admin:week', 'reports:admin:month'],
        ], $cacheStore->forgottenKeySets);
    }

    public function test_it_does_not_schedule_an_empty_invalidation(): void
    {
        $afterCommit = new DeferredAfterCommitDispatcher;
        $dispatcher = new CacheInvalidationDispatcher($afterCommit, new RecordingCacheStore);

        $dispatcher->forgetAfterCommit('', '');

        $this->assertSame([], $afterCommit->callbacks);
    }
}

final class DeferredAfterCommitDispatcher implements AfterCommitDispatcherInterface
{
    /**
     * @var array<int, Closure>
     */
    public array $callbacks = [];

    public function dispatch(Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    public function runCallbacks(): void
    {
        foreach ($this->callbacks as $callback) {
            $callback();
        }
    }
}

final class RecordingCacheStore implements CacheStoreInterface
{
    /**
     * @var array<int, array<int, string>>
     */
    public array $forgottenKeySets = [];

    public function remember(string $key, int $seconds, Closure $resolver): mixed
    {
        return $resolver();
    }

    public function rememberForever(string $key, Closure $resolver): mixed
    {
        return $resolver();
    }

    public function flexible(string $key, CacheTtl $ttl, Closure $resolver): mixed
    {
        return $resolver();
    }

    public function forget(string $key): void
    {
        $this->forgetMany([$key]);
    }

    public function forgetMany(array $keys): void
    {
        $this->forgottenKeySets[] = $keys;
    }

    public function increment(string $key, int $value = 1): int
    {
        return $value;
    }
}
