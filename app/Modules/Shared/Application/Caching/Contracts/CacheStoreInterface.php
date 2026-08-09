<?php

namespace App\Modules\Shared\Application\Caching\Contracts;

use App\Modules\Shared\Application\Caching\Data\CacheTtl;
use Closure;

interface CacheStoreInterface
{
    public function remember(string $key, int $seconds, Closure $resolver): mixed;

    public function rememberForever(string $key, Closure $resolver): mixed;

    public function flexible(string $key, CacheTtl $ttl, Closure $resolver): mixed;

    public function forget(string $key): void;

    /**
     * @param  array<int, string>  $keys
     */
    public function forgetMany(array $keys): void;

    public function increment(string $key, int $value = 1): int;
}
