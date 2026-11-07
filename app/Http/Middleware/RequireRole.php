<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $expected = UserRole::tryFrom($role);

        abort_unless($expected && $request->user()?->role === $expected, 403);

        return $next($request);
    }
}
