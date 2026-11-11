<?php

namespace App\Http\Controllers\Api\Mobile\Homeowner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\HandymanCardResource;
use App\Models\ActivityEvent;
use App\Models\HandymanProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Saved handymen, stored in the same handyman_favorites table as the website.
 */
class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $favorites = $request->user()->homeownerProfile->favoriteHandymen()
            ->whereHas('user', fn (Builder $query) => $query->where('status', 'active'))
            ->with(['user', 'skills'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByPivot('created_at', 'desc')
            ->get()
            ->each(fn (HandymanProfile $profile) => $profile->setAttribute('is_favorite', true));

        return HandymanCardResource::collection($favorites);
    }

    public function store(Request $request, HandymanProfile $handyman): JsonResponse
    {
        abort_unless($handyman->user?->isActive(), 404);
        $changes = $request->user()->homeownerProfile->favoriteHandymen()->syncWithoutDetaching([$handyman->user_id]);

        if ($changes['attached'] !== []) {
            ActivityEvent::record($request->user(), 'favorite.added', 'handyman', $handyman->user_id);
        }

        return response()->json(['data' => ['handyman_id' => $handyman->user_id, 'is_favorite' => true]]);
    }

    public function destroy(Request $request, HandymanProfile $handyman): JsonResponse
    {
        $removed = $request->user()->homeownerProfile->favoriteHandymen()->detach($handyman->user_id);

        if ($removed > 0) {
            ActivityEvent::record($request->user(), 'favorite.removed', 'handyman', $handyman->user_id);
        }

        return response()->json(['data' => ['handyman_id' => $handyman->user_id, 'is_favorite' => false]]);
    }
}
