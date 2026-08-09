<?php

namespace Tests\Unit\Caching;

use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;
use Tests\TestCase;

class LaravelCacheStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('shelfspot_cache.enabled', true);
        config()->set('shelfspot_cache.store', 'array');
    }

    public function test_it_returns_the_cached_value_without_rerunning_the_resolver(): void
    {
        $cacheStore = app(CacheStoreInterface::class);
        $resolverCalls = 0;
        $key = 'test:cache-store:'.uniqid();

        $first = $cacheStore->remember($key, 60, function () use (&$resolverCalls): array {
            $resolverCalls++;

            return ['value' => 'cached'];
        });
        $second = $cacheStore->remember($key, 60, function () use (&$resolverCalls): array {
            $resolverCalls++;

            return ['value' => 'new'];
        });

        $this->assertSame(['value' => 'cached'], $first);
        $this->assertSame(['value' => 'cached'], $second);
        $this->assertSame(1, $resolverCalls);
    }

    public function test_it_bypasses_the_cache_when_globally_disabled(): void
    {
        config()->set('shelfspot_cache.enabled', false);

        $cacheStore = app(CacheStoreInterface::class);
        $resolverCalls = 0;

        $cacheStore->remember('test:cache-store:disabled', 60, function () use (&$resolverCalls): int {
            return ++$resolverCalls;
        });
        $cacheStore->remember('test:cache-store:disabled', 60, function () use (&$resolverCalls): int {
            return ++$resolverCalls;
        });

        $this->assertSame(2, $resolverCalls);
    }
}
