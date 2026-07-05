<?php

namespace App\Modules\V1\Authentication\Infrastructure\Social;

use App\Modules\V1\Authentication\Domain\Contracts\SocialPortalRegistrarInterface;
use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SocialPortalRegistrarManager
{
    /**
     * @param array<string, SocialPortalRegistrarInterface> $registrars
     */
    public function __construct(private array $registrars)
    {
    }

    public function for(SocialProviderEnum $provider, PortalTypeEnum $portal): SocialPortalRegistrarInterface
    {
        $this->ensurePortalAllowed($provider, $portal);

        return $this->registrars[$portal->value]
            ?? throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
    }

    private function ensurePortalAllowed(SocialProviderEnum $provider, PortalTypeEnum $portal): void
    {
        $allowedPortals = config("social_auth.providers.{$provider->value}.allowed_portals", []);

        if (! in_array($portal->value, $allowedPortals, true)) {
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }
    }
}
