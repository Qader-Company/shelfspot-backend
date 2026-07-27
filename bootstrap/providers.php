<?php

use App\Providers\AppServiceProvider;
use App\Providers\RateLimitServiceProvider;
use App\Providers\TaskEventServiceProvider;
use Ejarnutowski\LaravelApiKey\Providers\ApiKeyServiceProvider;

return [
    RateLimitServiceProvider::class,
    AppServiceProvider::class,
    TaskEventServiceProvider::class,
    ApiKeyServiceProvider::class,
];
