<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gatekeeper for every authenticated mobile API request (runs after auth:sanctum).
 *
 * The token must be a mobile device token, and the account must still be an active, verified
 * homeowner or handyman. Tokens of accounts that were blocked, closed or are administrators are
 * revoked on sight, so a stolen or stale token stops working at the very next request.
 */
class EnsureMobileAccount
{
    public const TOKEN_ABILITY = 'mobile';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user instanceof User || ! $token instanceof PersonalAccessToken || ! $token->can(self::TOKEN_ABILITY)) {
            return self::refuse('unauthenticated', 'Please sign in again.', 401);
        }

        if ($user->status !== 'active' || $user->trashed()) {
            $token->delete();

            return self::refuse('account_unavailable', 'This account is unavailable.', 403);
        }

        if (! $user->canUseMobileApp()) {
            $token->delete();

            return self::refuse('role_not_supported', 'Administrator accounts sign in on the BreIn website.', 403);
        }

        if (! $user->hasVerifiedEmail()) {
            return self::refuse('email_unverified', 'Confirm your email address to use BreIn.', 403);
        }

        return $next($request);
    }

    /**
     * Uniform JSON refusal understood by the mobile client: a human message plus a stable code.
     */
    public static function refuse(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message, 'code' => $code], $status);
    }
}
