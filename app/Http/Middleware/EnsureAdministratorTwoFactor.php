<?php

namespace App\Http\Middleware;

use App\Actions\Accounts\AdministratorSession;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * An administrator session is only valid when it was created by the two-step sign-in. Any other
 * administrator session (remember-me cookie, session restored without the second factor) is
 * ended. Non-administrators pass through unchanged.
 */
class EnsureAdministratorTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isAdministrator() && ! AdministratorSession::hasPassedSecondFactor($request, $user)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('admin.login')->withErrors(['email' => 'Please sign in again to continue.']);
        }

        return $next($request);
    }
}
