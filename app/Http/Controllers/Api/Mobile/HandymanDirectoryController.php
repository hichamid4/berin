<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\CatalogServiceResource;
use App\Http\Resources\Mobile\HandymanCardResource;
use App\Http\Resources\Mobile\ReviewResource;
use App\Models\HandymanProfile;
use App\Models\Skill;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * "Find handymen": the public directory, filtered and ranked like the website.
 */
class HandymanDirectoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $city = trim($request->string('city')->toString());
        $search = trim($request->string('q')->toString());
        $skillId = $request->integer('skill') ?: null;
        $serviceId = $request->integer('service') ?: null;

        $handymen = self::directory($request)
            ->when($city !== '', fn (Builder $query) => $query->where('city', 'like', "%{$city}%"))
            ->when($skillId, fn (Builder $query) => $query->whereHas('skills', fn (Builder $skills) => $skills->where('skills.id', $skillId)))
            ->when($serviceId, fn (Builder $query) => $query->whereHas('skills.services', fn (Builder $services) => $services->where('services.id', $serviceId)))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereHas('user', fn (Builder $users) => $users->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                ->orWhereHas('skills', fn (Builder $skills) => $skills->where('name', 'like', "%{$search}%"))))
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->paginate(20);

        return HandymanCardResource::collection($handymen)->additional(['meta' => [
            'skills' => Skill::query()->active()->orderBy('name')->get(['id', 'name']),
        ]]);
    }

    public function show(Request $request, HandymanProfile $handyman): JsonResponse
    {
        $profile = self::directory($request)->with(['skills.services.category'])->whereKey($handyman->user_id)->firstOrFail();

        $reviews = $profile->reviews()
            ->with(['offer.request.service', 'offer.request.homeowner.user'])
            ->latest('reviews.created_at')
            ->limit(30)
            ->get();

        $services = $profile->skills->flatMap->services->unique('id')->sortBy('name')->values();

        return response()->json(['data' => [
            ...HandymanCardResource::make($profile)->resolve($request),
            'rating_breakdown' => collect([5, 4, 3, 2, 1])->mapWithKeys(
                fn (int $stars): array => [$stars => $profile->reviews()->where('rating', $stars)->count()],
            ),
            'services' => CatalogServiceResource::collection($services)->resolve($request),
            'reviews' => ReviewResource::collection($reviews)->resolve($request),
        ]]);
    }

    /**
     * Active handymen with their public statistics and whether the viewer saved them.
     *
     * @return Builder<HandymanProfile>
     */
    private static function directory(Request $request): Builder
    {
        $viewer = $request->user();

        return HandymanProfile::query()
            ->whereHas('user', fn (Builder $query) => $query->where('status', 'active'))
            ->with(['user', 'skills'])
            ->withCount(['reviews', 'favoritedBy'])
            ->withCount(['offers as completed_jobs_count' => fn (Builder $query) => $query
                ->where('status', OfferStatus::Accepted)
                ->whereHas('request', fn (Builder $requests) => $requests->where('status', RequestStatus::Completed))])
            ->withAvg('reviews', 'rating')
            ->when($viewer->isHomeowner(), fn (Builder $query) => $query->withExists([
                'favoritedBy as is_favorite' => fn (Builder $favorites) => $favorites->where('handyman_favorites.homeowner_id', $viewer->id),
            ]));
    }
}
