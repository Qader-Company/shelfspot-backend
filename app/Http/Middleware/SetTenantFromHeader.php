<?php

namespace App\Http\Middleware;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromHeader
{
    public function __construct(private TenantContextInterface $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenantSlug = $request->header('X-Tenant-Slug')
            ?? $request->header('X-Company-Slug')
            ?? $request->header('X-Tenant')
            ?? $request->header('X-Company');

        if (filled($tenantSlug) && ! $this->tenantContext->setSlug($tenantSlug)) {
            abort(404, 'Tenant not found.');
        }

        return $next($request);
    }
}
