<?php

use App\Http\Middleware\EnsureAdministratorTwoFactor;
use App\Http\Middleware\EnsureMobileAccount;
use App\Http\Middleware\PrivateAdministrationArea;
use App\Http\Middleware\RequireActiveAccount;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => RequireActiveAccount::class,
            'role' => RequireRole::class,
            'admin.2fa' => EnsureAdministratorTwoFactor::class,
            'admin.private' => PrivateAdministrationArea::class,
            'mobile' => EnsureMobileAccount::class,
        ]);
        // Account-state, second-factor and role checks run before route-model binding, so a refused
        // request can never learn whether a record ID exists (no 404 versus 403 difference).
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureMobileAccount::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, RequireActiveAccount::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureAdministratorTwoFactor::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, RequireRole::class);
        // Administration URLs are private (see config/admin.php), so they are recognised by route name, not by path.
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->routeIs('admin.*')
            ? route('admin.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Expired, tampered or mismatched verification links get an explanatory page with a resend option.
        $exceptions->render(function (InvalidSignatureException|HttpException $exception, Request $request) {
            if ($request->routeIs('verification.verify') && $exception->getStatusCode() === 403 && ! $request->expectsJson()) {
                return response()->view('auth.link-invalid', [], 403);
            }

            return null;
        });
    })->create();
