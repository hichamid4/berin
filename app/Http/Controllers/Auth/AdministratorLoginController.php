<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Accounts\AdministratorSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Models\ActivityEvent;
use App\Models\User;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * First step of administrator sign-in, reachable only under the private administration path.
 * A correct password never logs the administrator in by itself; it opens the second-factor step.
 */
class AdministratorLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login', ['role' => UserRole::Administrator->value]);
    }

    public function store(LoginUserRequest $request, AdministratorSession $session): RedirectResponse
    {
        $credentials = [
            'email' => mb_strtolower($request->validated('email')),
            'password' => $request->validated('password'),
            'role' => UserRole::Administrator->value,
            'status' => 'active',
        ];

        $guard = Auth::guard('web');

        if (! $guard->validate($credentials)) {
            $knownAdministrator = User::query()->where('email', $credentials['email'])->where('role', UserRole::Administrator)->value('id');
            ActivityEvent::record(null, 'admin.sign_in_failed', 'user', $knownAdministrator, ['step' => 'password']);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        /** @var User $administrator */
        $administrator = $guard->getLastAttempted();

        if (! $administrator->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['email' => 'This administrator account has not verified its email address.']);
        }

        $session->startPending($request, $administrator);

        return $administrator->twoFactorCredential()->whereNotNull('confirmed_at')->exists()
            ? to_route('admin.two-factor.challenge')
            : to_route('admin.two-factor.setup');
    }
}
