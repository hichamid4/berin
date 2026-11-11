<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorizes the app's private WebSocket subscriptions with the device token.
 *
 * The channel definitions are loaded here, on this API request only, so website requests never
 * load the broadcasting layer and keep behaving exactly as before.
 */
class BroadcastAuthController extends Controller
{
    public function __invoke(Request $request): Response
    {
        require base_path('routes/channels.php');

        $result = Broadcast::auth($request);

        return $result instanceof Response ? $result : response()->json($result);
    }
}
