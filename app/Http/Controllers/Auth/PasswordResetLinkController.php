<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendPasswordResetLinkRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(SendPasswordResetLinkRequest $request): RedirectResponse
    {
        $email = mb_strtolower($request->validated('email'));

        if (User::query()->where('email', $email)->where('status', 'active')->exists()) {
            Password::sendResetLink(['email' => $email]);
        }

        // Do not disclose whether an account exists, is blocked, or has been anonymized.
        return back()->with('status', 'password-reset-link-sent');
    }
}
