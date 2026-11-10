<?php

use App\Http\Middleware\RequireActiveAccount;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => RequireActiveAccount::class,
            'role' => RequireRole::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('admin/*')
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
