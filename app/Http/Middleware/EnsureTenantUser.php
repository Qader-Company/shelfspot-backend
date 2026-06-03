<?php

namespace App\Http\Middleware;

use App\Facades\ApiResponse;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUser
{
    public function __construct(private readonly TenantContextInterface $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $company = $this->tenantContext->getCompany();

        if (! $user || ! $company || $user->type !== PortalTypeEnum::COMPANY) {
            return ApiResponse::forbidden('apiMessages.forbidden');
        }

        $belongsToCompany = $user->companyUser()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereHas('company', fn ($query) => $query->where('is_active', true))
            ->exists();

        if (! $belongsToCompany) {
            return ApiResponse::forbidden('apiMessages.forbidden');
        }

        return $next($request);
    }
}
