<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? to_route($request->user()->dashboardRouteName())
            : view('auth.verify-email');
    }

    /**
     * Verify an address from the signed link in the email.
     *
     * The temporary signature (checked by the "signed" middleware) and the email hash prove the
     * link is genuine, so it also works when opened in another browser or after the session ended.
     */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->whereKey($id)->where('status', 'active')->first();

        abort_if($user === null || $user->email === null, 403, 'This verification link is not valid for an active account.');
        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403, 'This verification link does not match the account email address.');

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        if ($request->user()?->is($user)) {
            return to_route($user->dashboardRouteName())->with('status', 'email-verified');
        }

        return to_route($user->role->loginRouteName())->with('status', 'email-verified-sign-in');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route($request->user()->dashboardRouteName());
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
