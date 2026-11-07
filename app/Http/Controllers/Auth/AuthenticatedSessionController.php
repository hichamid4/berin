<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(string $role = 'homeowner')
    {
        abort_unless(in_array($role, [UserRole::Homeowner->value, UserRole::Handyman->value, UserRole::Administrator->value], true), 404);

        return view('auth.login', ['role' => $role]);
    }

    public function store(LoginUserRequest $request, string $role = 'homeowner'): RedirectResponse
    {
        abort_unless(in_array($role, [UserRole::Homeowner->value, UserRole::Handyman->value, UserRole::Administrator->value], true), 404);
        $credentials = $request->safe()->only(['email', 'password']);
        $credentials['email'] = mb_strtolower($credentials['email']);
        $credentials['status'] = 'active';
        $credentials['role'] = $role;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        return $user->hasVerifiedEmail()
            ? to_route($user->dashboardRouteName())
            : to_route('verification.notice');
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return to_route('home');
    }
}
