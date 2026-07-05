<?php

namespace App\Modules\V1\Authentication\Infrastructure\Social;

use App\Modules\V1\Authentication\Domain\Contracts\SocialProviderVerifierInterface;
use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use InvalidArgumentException;

class SocialProviderManager
{
    /**
     * @param array<string, SocialProviderVerifierInterface> $providers
     */
    public function __construct(private array $providers)
    {
    }

    public function driver(SocialProviderEnum $provider): SocialProviderVerifierInterface
    {
        return $this->providers[$provider->value]
            ?? throw new InvalidArgumentException("Unsupported social provider [{$provider->value}].");
    }
}
