<?php

namespace App\Modules\Shared\Application\Caching\Contracts;

use Closure;

interface AfterCommitDispatcherInterface
{
    public function dispatch(Closure $callback): void;
}
