<?php

namespace App\Modules\Shared\Infrastructure\Caching;

use App\Modules\Shared\Application\Caching\Contracts\AfterCommitDispatcherInterface;
use Closure;
use Illuminate\Support\Facades\DB;

final class LaravelAfterCommitDispatcher implements AfterCommitDispatcherInterface
{
    public function dispatch(Closure $callback): void
    {
        DB::afterCommit($callback);
    }
}
