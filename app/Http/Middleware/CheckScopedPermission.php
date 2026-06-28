<?php

namespace App\Http\Middleware;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckScopedPermission
{
    public function __construct(private readonly TenantContextInterface $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next, string $permissions, ?string $guard = null): Response
    {
        $user = $request->user($guard);

        if (! $user) {
            throw new AccessDeniedHttpException();
        }

        $portal = $this->portalFor($user->type);
        $companyId = $portal === PermissionCatalog::COMPANY_PORTAL
            ? $this->tenantContext->getCompanyId()
            : null;

        if ($portal === PermissionCatalog::COMPANY_PORTAL && ! $companyId) {
            throw new AccessDeniedHttpException();
        }

        $permissionNames = $this->parsePipeSeparatedValues($permissions);
        $hasPermissionThroughRole = $user->roles()
            ->where('portal', $portal)
            ->where('company_id', $companyId)
            ->whereHas('permissions', function ($query) use ($permissionNames, $portal) {
                $query->whereIn('name', $permissionNames)
                    ->where('portal', $portal);
            })
            ->exists();

        if (! $hasPermissionThroughRole) {
            throw new AccessDeniedHttpException();
        }

        return $next($request);
    }

    private function portalFor(PortalTypeEnum $type): string
    {
        return match ($type) {
            PortalTypeEnum::ADMIN => PermissionCatalog::ADMIN_PORTAL,
            PortalTypeEnum::COMPANY => PermissionCatalog::COMPANY_PORTAL,
            default => throw new AccessDeniedHttpException(),
        };
    }

    /**
     * @return array<int, string>
     */
    private function parsePipeSeparatedValues(string $values): array
    {
        return array_values(array_filter(array_map('trim', explode('|', $values))));
    }
}
