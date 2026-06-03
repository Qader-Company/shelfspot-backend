<?php

namespace App\Http\Middleware;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Companies\Domain\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromRouteCompany
{
    public function __construct(private TenantContextInterface $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->route('company');
        $company = Company::withoutGlobalScopes()->find($companyId);

        if (! $company) {
            abort(404, 'Company not found.');
        }

        $this->tenantContext->setCompany($company);

        return $next($request);
    }
}
