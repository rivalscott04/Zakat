<?php

use App\Enums\ErrorCode;
use App\Exceptions\ZakatException;
use App\Http\Middleware\ApplySettings;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\ResolveOrganizationContext;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // PRD 18V §39 — API publik berada di luar prefix /api/v1.
            Route::middleware('api')
                ->prefix('api/public')
                ->group(base_path('routes/public.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // PRD 01 §17 — SPA React memakai Sanctum cookie-based authentication.
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
            AssignRequestId::class,
            ApplySettings::class,
        ]);

        $middleware->alias([
            'permission' => EnsurePermission::class,
            'organization.context' => ResolveOrganizationContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // PRD 00 §17 dan §36 — satu bentuk error envelope untuk seluruh API.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $e instanceof ZakatException => ApiResponse::error($e->errorCode, $e->getMessage(), $e->errors),

                $e instanceof ValidationException => ApiResponse::error(
                    ErrorCode::ValidationError, $e->getMessage(), $e->errors()
                ),

                $e instanceof AuthenticationException => ApiResponse::error(
                    ErrorCode::Unauthorized, 'Autentikasi diperlukan.'
                ),

                $e instanceof AuthorizationException => ApiResponse::error(
                    ErrorCode::Forbidden, $e->getMessage() ?: 'Akses ditolak.'
                ),

                $e instanceof ThrottleRequestsException => ApiResponse::error(
                    ErrorCode::TooManyRequests, 'Terlalu banyak percobaan. Coba lagi nanti.'
                ),

                // Resource yang tidak terlihat oleh scope organisasi dilaporkan sebagai
                // 404, bukan 403, supaya tidak membocorkan keberadaannya (PRD 22 — enumeration).
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => ApiResponse::error(
                    ErrorCode::NotFound, 'Data tidak ditemukan.'
                ),

                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    ErrorCode::ServerError, $e->getMessage() ?: 'Permintaan tidak dapat diproses.', [], $e->getStatusCode()
                ),

                default => null,
            };
        });
    })->create();
