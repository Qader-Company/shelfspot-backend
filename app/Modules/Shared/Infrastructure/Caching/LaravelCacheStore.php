<?php

namespace App\Modules\Shared\Infrastructure\Caching;

use App\Modules\Shared\Application\Caching\Contracts\CacheStoreInterface;
use App\Modules\Shared\Application\Caching\Data\CacheTtl;
use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class LaravelCacheStore implements CacheStoreInterface
{
    public function remember(string $key, int $seconds, Closure $resolver): mixed
    {
        if (! $this->enabled()) {
            return $resolver();
        }

        $missing = new \stdClass;

        try {
            $value = $this->store()->get($key, $missing);
        } catch (Throwable $exception) {
            return $this->resolveWithoutCache($key, $resolver, $exception);
        }

        if ($value !== $missing) {
            return $value;
        }

        $value = $resolver();

        try {
            $this->store()->put($key, $value, now()->addSeconds($seconds));
        } catch (Throwable $exception) {
            $this->logFailure('write', $key, $exception);
        }

        return $value;
    }

    public function flexible(string $key, CacheTtl $ttl, Closure $resolver): mixed
    {
        if (! $this->enabled()) {
            return $resolver();
        }

        $resolverStarted = false;

        try {
            return $this->store()->flexible(
                $key,
                $ttl->toFlexibleDurations(),
                function () use ($resolver, &$resolverStarted): mixed {
                    $resolverStarted = true;

                    return $resolver();
                }
            );
        } catch (Throwable $exception) {
            if ($resolverStarted) {
                throw $exception;
            }

            return $this->resolveWithoutCache($key, $resolver, $exception);
        }
    }

    public function rememberForever(string $key, Closure $resolver): mixed
    {
        if (! $this->enabled()) {
            return $resolver();
        }

        $missing = new \stdClass;

        try {
            $value = $this->store()->get($key, $missing);
        } catch (Throwable $exception) {
            return $this->resolveWithoutCache($key, $resolver, $exception);
        }

        if ($value !== $missing) {
            return $value;
        }

        $value = $resolver();

        try {
            $this->store()->forever($key, $value);
        } catch (Throwable $exception) {
            $this->logFailure('write', $key, $exception);
        }

        return $value;
    }

    public function forget(string $key): void
    {
        $this->forgetMany([$key]);
    }

    public function forgetMany(array $keys): void
    {
        if (! $this->enabled()) {
            return;
        }

        foreach (array_unique($keys) as $key) {
            try {
                $this->store()->forget($key);
            } catch (Throwable $exception) {
                $this->logFailure('forget', $key, $exception);
            }
        }
    }

    public function increment(string $key, int $value = 1): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        try {
            return $this->store()->increment($key, $value);
        } catch (Throwable $exception) {
            $this->logFailure('increment', $key, $exception);

            return 0;
        }
    }

    private function enabled(): bool
    {
        return (bool) config('shelfspot_cache.enabled', true);
    }

    private function store(): Repository
    {
        return Cache::store(config('shelfspot_cache.store'));
    }

    private function resolveWithoutCache(string $key, Closure $resolver, Throwable $exception): mixed
    {
        $this->logFailure('read', $key, $exception);

        return $resolver();
    }

    private function logFailure(string $operation, string $key, Throwable $exception): void
    {
        Log::warning('Cache store operation failed; falling back to the source of truth where possible.', [
            'operation' => $operation,
            'key' => $key,
            'store' => config('shelfspot_cache.store'),
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }
}
