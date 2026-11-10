<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Accounts\AdministratorTwoFactor;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.security', [
            'credential' => $request->user()->twoFactorCredential()->firstOrFail(),
            'recoveryCodes' => $request->session()->get('recovery_codes'),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request, AdministratorTwoFactor $twoFactor): RedirectResponse
    {
        $request->validate(
            ['password' => ['required', 'current_password']],
            ['password.current_password' => 'The password is incorrect.', 'password.required' => 'Enter your password to generate new recovery codes.'],
        );

        return to_route('admin.security')
            ->with('recovery_codes', $twoFactor->regenerateRecoveryCodes($request->user()))
            ->with('success', 'New recovery codes were generated. The previous codes no longer work.');
    }
}
