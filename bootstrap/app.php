<?php

use App\Facades\ApiResponse;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantFromHeader;
use App\Http\Middleware\SetTenantFromRouteCompany;
use App\Http\Middleware\EnsureTenantUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\RoleMiddleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'locale' => SetLocale::class,
            'role' => RoleMiddleware::class,
            'tenant' => SetTenantFromHeader::class,
            'tenant.route-company' => SetTenantFromRouteCompany::class,
            'tenant.user' => EnsureTenantUser::class,
            'api.key' => \App\Http\Middleware\CheckApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
//
//        $exceptions->render(function (ValidationException $e, $request) {
//            return ApiResponse::validationError(
//                $e->errors(),
//                $e->getMessage(),
//            );
//        });
//
//        $exceptions->render(function (UnauthorizedException $e, $request) {
//            return ApiResponse::unauthorized('auth.unauthorized');
//        });
//
//        $exceptions->render(function (AuthenticationException $e, $request) {
//            return ApiResponse::unauthorized('auth.unauthorized');
//        });

//        $exceptions->render(function (AccessDeniedHttpException|AuthorizationException $e, $request) {
//            $message = $e->getMessage() ?: 'api.forbidden';
//            return ApiResponse::forbidden($message);
//        });
//
//        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, $request) {
//            return ApiResponse::notFound($e->getMessage());
//        });
//
//        $exceptions->render(function (\DomainException $e, $request) {
//            return ApiResponse::message($e->getMessage(), 400);
//        });
//
//        $exceptions->render(function (\InvalidArgumentException $e, $request) {
//            return ApiResponse::message($e->getMessage(), 422);
//        });
//
//        $exceptions->render(function (ThrottleRequestsException $e, $request) {
//            $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 60);
//
//            return ApiResponse::tooManyRequests($retryAfter);
//        });
//
//        $exceptions->render(function (HttpException $e, $request) {
//            return ApiResponse::message($e->getMessage(), $e->getStatusCode());
//        });

    })->create();
