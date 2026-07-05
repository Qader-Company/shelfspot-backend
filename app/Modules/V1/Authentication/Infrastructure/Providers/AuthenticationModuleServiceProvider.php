<?php

namespace App\Modules\V1\Authentication\Infrastructure\Providers;

use App\Modules\V1\Authentication\Domain\Contracts\SocialAccountRepositoryInterface;
use App\Modules\V1\Authentication\Infrastructure\Persistence\Repositories\EloquentSocialAccountRepository;
use App\Modules\V1\Authentication\Infrastructure\Social\GoogleTokenVerifier;
use App\Modules\V1\Authentication\Infrastructure\Social\SocialProviderManager;
use App\Providers\AppServiceProvider;
use Google\Client as GoogleClient;

class AuthenticationModuleServiceProvider extends AppServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SocialAccountRepositoryInterface::class, EloquentSocialAccountRepository::class);

        $this->app->singleton(GoogleClient::class, function () {
            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));

            return $client;
        });

        $this->app->singleton(SocialProviderManager::class, fn ($app) => new SocialProviderManager([
            'google' => $app->make(GoogleTokenVerifier::class),
        ]));
    }
}
