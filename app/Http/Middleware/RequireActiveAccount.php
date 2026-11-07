<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isActive = $user instanceof User && User::query()
            ->whereKey($user->getKey())
            ->where('status', 'active')
            ->exists();

        if (! $isActive) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')->withErrors(['email' => 'This account is unavailable.']);
        }

        return $next($request);
    }
}
