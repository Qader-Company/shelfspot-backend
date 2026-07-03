<?php

namespace App\Http\Middleware;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class SetTenant
{
    public function __construct(private TenantContextInterface $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenantID = $request->header('X-Company-id') ?? $request->route('company');
        if (!filled($tenantID)  || ! $this->tenantContext->setCompany($tenantID)) {
            abort(404, 'Company not found.');
        }

        return $next($request);
    }
}
