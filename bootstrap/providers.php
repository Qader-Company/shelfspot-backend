<?php

use App\Providers\AppServiceProvider;
use App\Providers\RateLimitServiceProvider;

return [
    RateLimitServiceProvider::class,
    AppServiceProvider::class,
    Ejarnutowski\LaravelApiKey\Providers\ApiKeyServiceProvider::class
];
