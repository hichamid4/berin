<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Connection settings for the app's WebSocket client. Only the public Reverb app key is shared;
 * private channels are authorized by the API with the device token, never with the app secret.
 */
class RealtimeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => [
            'driver' => 'reverb',
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('mobile.realtime.host'),
            'port' => config('mobile.realtime.port'),
            'scheme' => config('mobile.realtime.scheme'),
            'auth_endpoint' => route('api.mobile.broadcasting.auth'),
        ]]);
    }
}
