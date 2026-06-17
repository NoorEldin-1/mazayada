<?php

use App\Http\Middleware\ApiSetLocale;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureTwoFactorForAdmins;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ApiKycVerified;
use App\Http\Middleware\KycVerified;
use App\Http\Middleware\NoCacheHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->appendToGroup('web', NoCacheHeaders::class);

        // Mobile API: sessionless locale resolution + force-JSON so framework
        // error handling (auth redirect, validation) always uses the JSON branch.
        $middleware->appendToGroup('api', ApiSetLocale::class);
        $middleware->appendToGroup('api', ForceJsonResponse::class);

        // The dashboard light/dark theme is stored in a plain (unencrypted)
        // cookie so JS can write it and Blade can read it back verbatim for
        // server-rendered <html data-theme> (no flash of wrong theme).
        $middleware->encryptCookies(except: ['theme']);

        $middleware->alias([
            'role'           => EnsureRole::class,
            'kyc.verified'   => KycVerified::class,
            'admin.2fa'      => EnsureTwoFactorForAdmins::class,
            // Mobile API aliases.
            'api.kyc'        => ApiKycVerified::class,
            'active.account' => EnsureActiveAccount::class,
            'abilities'      => CheckAbilities::class,
            'ability'        => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render every exception thrown under /api/* as the unified JSON error
        // shape { message, errors? }. Web requests fall through (return null) so
        // their redirect/HTML behaviour is unchanged.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => __('common.api.unauthenticated'),
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'message' => $e->getMessage() ?: __('common.api.forbidden'),
                ], 403);
            }

            if ($e instanceof ThrottleRequestsException) {
                return response()->json([
                    'message' => __('common.api.too_many_requests'),
                ], 429)->withHeaders($e->getHeaders());
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => __('common.api.not_found'),
                ], 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => $e->getMessage() ?: __('common.api.error'),
                ], $e->getStatusCode())->withHeaders($e->getHeaders());
            }

            $payload = [
                'message' => config('app.debug') ? $e->getMessage() : __('common.api.server_error'),
            ];

            if (config('app.debug')) {
                $payload['exception'] = $e::class;
            }

            return response()->json($payload, 500);
        });
    })->create();
