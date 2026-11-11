<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMobileAccount;
use App\Http\Requests\Mobile\MobileLoginRequest;
use App\Http\Resources\Mobile\ProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;

/**
 * Sign-in and sign-out for the mobile app, using one Sanctum token per device.
 *
 * The role is read from the account (homeowner or handyman); administrators, blocked and closed
 * accounts are refused. As on the website, the email address must be verified before use.
 */
class AuthController extends Controller
{
    public function login(MobileLoginRequest $request): JsonResponse
    {
        $user = $this->verifiedCredentials(mb_strtolower($request->validated('email')), $request->validated('password'));

        if ($user === null) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($user->status !== 'active' || $user->trashed()) {
            return EnsureMobileAccount::refuse('account_unavailable', 'This account is unavailable.', 403);
        }

        if (! $user->canUseMobileApp()) {
            return EnsureMobileAccount::refuse('role_not_supported', 'Administrator accounts sign in on the BreIn website.', 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $this->resendVerificationLink($user);

            return EnsureMobileAccount::refuse('email_unverified', 'Confirm your email address first. We sent a verification link to your inbox.', 403);
        }

        $deviceId = $request->validated('device_id');
        $expiresAt = now()->addDays(config('mobile.token_lifetime_days'));

        $token = DB::transaction(function () use ($user, $deviceId, $request, $expiresAt) {
            $user->tokens()->where('name', 'like', "mobile|{$deviceId}|%")->delete();

            return $user->createToken(
                'mobile|'.$deviceId.'|'.Str::limit($request->validated('device_name'), 80, ''),
                [EnsureMobileAccount::TOKEN_ABILITY],
                $expiresAt,
            );
        });

        $user->load(['homeownerProfile', 'handymanProfile']);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601ZuluString('millisecond'),
            'user' => ProfileResource::make($user),
        ]);
    }

    /**
     * Revoke only the token of the device that signs out; other devices stay signed in.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Check the password in constant time, like the web guard, so response timing does not
     * reveal whether an email address has an account.
     */
    private function verifiedCredentials(string $email, string $password): ?User
    {
        return (new Timebox)->call(function (Timebox $timebox) use ($email, $password): ?User {
            $user = User::query()->where('email', $email)->first();

            if ($user !== null && Hash::check($password, $user->password)) {
                $timebox->returnEarly();

                return $user;
            }

            return null;
        }, 200_000);
    }

    /**
     * Send a fresh verification link at most once every ten minutes per account.
     */
    private function resendVerificationLink(User $user): void
    {
        RateLimiter::attempt('mobile-verification-link|'.$user->id, 1, function () use ($user): void {
            rescue(fn () => $user->sendEmailVerificationNotification());
        }, 600);
    }
}
