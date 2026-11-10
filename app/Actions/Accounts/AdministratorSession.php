<?php

namespace App\Actions\Accounts;

use App\Models\ActivityEvent;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Two-step administrator sign-in. A correct password only creates a short-lived pending state;
 * the administrator is logged in after the second factor succeeds, and the session is then
 * marked so EnsureAdministratorTwoFactor can refuse any administrator session created
 * another way (for example a remember-me cookie).
 */
class AdministratorSession
{
    public const PENDING_KEY = 'administrator_login.pending';

    public const PASSED_KEY = 'administrator_login.two_factor_passed';

    public function startPending(Request $request, User $administrator): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::PENDING_KEY, [
            'user_id' => $administrator->id,
            'expires_at' => now()->addMinutes((int) config('admin.two_factor.pending_login_minutes', 10))->getTimestamp(),
        ]);
    }

    /**
     * The administrator whose password was just verified, while the pending state is still valid
     * and the account is still an active, verified administrator.
     */
    public function pendingAdministrator(Request $request): ?User
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            $this->clearPending($request);

            return null;
        }

        $administrator = User::query()
            ->whereKey($pending['user_id'] ?? null)
            ->where('role', UserRole::Administrator)
            ->where('status', 'active')
            ->whereNotNull('email_verified_at')
            ->first();

        if ($administrator === null) {
            $this->clearPending($request);
        }

        return $administrator;
    }

    public function clearPending(Request $request): void
    {
        $request->session()->forget(self::PENDING_KEY);
    }

    /**
     * Log the administrator in after a successful second factor. Remember-me is never used.
     */
    public function complete(Request $request, User $administrator, string $method): void
    {
        $this->clearPending($request);
        Auth::guard('web')->login($administrator, false);
        $request->session()->regenerate();
        $request->session()->put(self::PASSED_KEY, $administrator->id);

        ActivityEvent::record($administrator, 'admin.signed_in', 'user', $administrator->id, ['second_factor' => $method]);
    }

    public static function hasPassedSecondFactor(Request $request, User $administrator): bool
    {
        return $request->hasSession() && $request->session()->get(self::PASSED_KEY) === $administrator->id;
    }
}
