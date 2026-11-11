<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\MobileRegisterRequest;
use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Account creation from the app, with the website's rules and records. As on the website, the
 * email address must be confirmed through the emailed link before the member can sign in.
 */
class RegistrationController extends Controller
{
    public function store(MobileRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'role' => $data['role'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => mb_strtolower($data['email']),
                'phone_e164' => $data['phone_e164'] ?? null,
                'password' => $data['password'],
            ]);

            $profile = ['user_id' => $user->id, 'address' => $data['address'] ?? null, 'city' => $data['city'] ?? null];
            $data['role'] === UserRole::Homeowner->value ? HomeownerProfile::create($profile) : HandymanProfile::create($profile);

            return $user;
        });

        event(new Registered($user));

        return response()->json([
            'message' => 'Your account was created. Open the link we emailed to '.$user->email.' to activate it, then sign in.',
            'requires_email_verification' => true,
        ], 201);
    }
}
