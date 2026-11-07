<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function create(string $role)
    {
        abort_unless(in_array($role, [UserRole::Homeowner->value, UserRole::Handyman->value], true), 404);

        return view('auth.register', ['role' => $role]);
    }

    public function store(RegisterUserRequest $request, string $role, DatabaseManager $database): RedirectResponse
    {
        abort_unless(in_array($role, [UserRole::Homeowner->value, UserRole::Handyman->value], true), 404);
        $data = $request->validated();

        $user = $database->transaction(function () use ($data, $role): User {
            $user = User::create([
                'role' => $role,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => mb_strtolower($data['email']),
                'phone_e164' => $data['phone_e164'] ?? null,
                'password' => $data['password'],
            ]);

            if ($role === UserRole::Homeowner->value) {
                HomeownerProfile::create([
                    'user_id' => $user->id,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                ]);
            } else {
                HandymanProfile::create([
                    'user_id' => $user->id,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                ]);
            }

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('verification.notice')->with('status', 'verification-link-sent');
    }
}
