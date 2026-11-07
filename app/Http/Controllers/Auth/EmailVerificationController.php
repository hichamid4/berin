<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice()
    {
        return request()->user()->hasVerifiedEmail()
            ? to_route(request()->user()->dashboardRouteName())
            : view('auth.verify-email');
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless(hash_equals((string) $request->route('id'), (string) $user->getKey()), 403);
        abort_unless(hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return to_route($user->dashboardRouteName())->with('status', 'email-verified');
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
