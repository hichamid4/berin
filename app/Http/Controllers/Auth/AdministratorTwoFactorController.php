<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Accounts\AdministratorSession;
use App\Actions\Accounts\AdministratorTwoFactor;
use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Second step of administrator sign-in: the authenticator-app challenge, and first-time
 * enrolment for an administrator who has no confirmed second factor yet.
 */
class AdministratorTwoFactorController extends Controller
{
    /**
     * Wrong codes allowed per pending sign-in before the password must be entered again.
     */
    public const MAX_ATTEMPTS = 5;

    public function __construct(private AdministratorSession $session, private AdministratorTwoFactor $twoFactor) {}

    public function challenge(Request $request): View|RedirectResponse
    {
        $administrator = $this->session->pendingAdministrator($request);

        if ($administrator === null) {
            return $this->restart();
        }

        if (! $this->hasConfirmedSecondFactor($administrator)) {
            return to_route('admin.two-factor.setup');
        }

        return view('auth.admin-two-factor-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $administrator = $this->session->pendingAdministrator($request);

        if ($administrator === null || ! $this->hasConfirmedSecondFactor($administrator)) {
            return $this->restart();
        }

        $request->validate(['code' => ['required', 'string', 'max:32']], ['code.required' => 'Enter the 6-digit code from your authenticator app or one of your recovery codes.']);

        if ($this->tooManyAttempts($request, $administrator)) {
            return $this->restart($this->lockoutMessage($administrator));
        }

        $method = $this->twoFactor->verify($administrator, $request->string('code')->toString());

        if ($method === null) {
            return $this->failedAttempt($administrator, 'challenge');
        }

        RateLimiter::clear($this->attemptKey($administrator));
        $this->session->complete($request, $administrator, $method);

        $remaining = (int) $administrator->twoFactorCredential()->first()?->remainingRecoveryCodes();
        $redirect = redirect()->intended(route('admin.dashboard'));

        return $method === 'recovery_code'
            ? $redirect->with('error', "You signed in with a recovery code. {$remaining} recovery ".($remaining === 1 ? 'code remains' : 'codes remain').'. Generate new codes from Security if you are running low.')
            : $redirect;
    }

    public function setup(Request $request): View|RedirectResponse
    {
        $administrator = $this->session->pendingAdministrator($request);

        if ($administrator === null) {
            return $this->restart();
        }

        if ($this->hasConfirmedSecondFactor($administrator)) {
            return to_route('admin.two-factor.challenge');
        }

        $credential = $this->twoFactor->beginEnrolment($administrator);

        return view('auth.admin-two-factor-setup', [
            'qrCode' => $this->twoFactor->qrCodeSvg($administrator, $credential),
            'secret' => $this->twoFactor->formattedSecret($credential),
            'accountName' => $administrator->email,
            'issuer' => config('admin.two_factor.issuer'),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $administrator = $this->session->pendingAdministrator($request);

        if ($administrator === null) {
            return $this->restart();
        }

        if ($this->hasConfirmedSecondFactor($administrator)) {
            return to_route('admin.two-factor.challenge');
        }

        $request->validate(['code' => ['required', 'string', 'max:32']], ['code.required' => 'Enter the 6-digit code shown in your authenticator app.']);

        if ($this->tooManyAttempts($request, $administrator)) {
            return $this->restart($this->lockoutMessage($administrator));
        }

        $recoveryCodes = $this->twoFactor->confirmEnrolment($administrator, $request->string('code')->toString());

        if ($recoveryCodes === null) {
            return $this->failedAttempt($administrator, 'enrolment');
        }

        RateLimiter::clear($this->attemptKey($administrator));
        $this->session->complete($request, $administrator, 'totp');

        return to_route('admin.security')
            ->with('recovery_codes', $recoveryCodes)
            ->with('success', 'Two-factor authentication is now active on your administrator account.');
    }

    private function hasConfirmedSecondFactor(User $administrator): bool
    {
        return $administrator->twoFactorCredential()->whereNotNull('confirmed_at')->exists();
    }

    private function attemptKey(User $administrator): string
    {
        return 'admin-two-factor-attempts|'.$administrator->id;
    }

    private function tooManyAttempts(Request $request, User $administrator): bool
    {
        if (! RateLimiter::tooManyAttempts($this->attemptKey($administrator), self::MAX_ATTEMPTS)) {
            return false;
        }

        $this->session->clearPending($request);

        return true;
    }

    private function lockoutMessage(User $administrator): string
    {
        $minutes = max(1, (int) ceil(RateLimiter::availableIn($this->attemptKey($administrator)) / 60));

        return "Too many incorrect codes. Wait {$minutes} ".($minutes === 1 ? 'minute' : 'minutes').', then sign in again with your password.';
    }

    private function failedAttempt(User $administrator, string $step): RedirectResponse
    {
        RateLimiter::hit($this->attemptKey($administrator), 900);
        ActivityEvent::record(null, 'admin.sign_in_failed', 'user', $administrator->id, ['step' => $step]);

        return back()->withErrors(['code' => 'That code is not valid. Check the time on your phone and try the newest code.']);
    }

    private function restart(string $message = 'Your sign-in expired. Enter your email and password again.'): RedirectResponse
    {
        return to_route('admin.login')->withErrors(['email' => $message]);
    }
}
