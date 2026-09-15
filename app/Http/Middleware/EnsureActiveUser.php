<?php

namespace App\Http\Middleware;

use App\Modules\V1\Users\Application\Services\UserActivationChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || ! $user->tokenCan($user->type->value)
            || ! UserActivationChecker::isActive($user, $user->type)
        ) {
            throw new AccessDeniedHttpException(__('api.forbidden'));
        }

        return $next($request);
    }
}
