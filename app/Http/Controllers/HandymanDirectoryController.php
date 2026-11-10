<?php

namespace App\Http\Controllers;

use App\Models\HandymanProfile;
use App\Models\Skill;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HandymanDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $city = trim($request->string('city')->toString());
        $skillId = $request->integer('skill') ?: null;

        $handymen = HandymanProfile::query()
            ->whereHas('user', fn (Builder $query) => $query->where('status', 'active'))
            ->when($city !== '', fn (Builder $query) => $query->where('city', 'like', "%{$city}%"))
            ->when($skillId, fn (Builder $query) => $query->whereHas('skills', fn (Builder $skills) => $skills->where('skills.id', $skillId)))
            ->with(['user', 'skills'])
            ->withCount(['reviews', 'favoritedBy'])
            ->withCount(['offers as completed_jobs_count' => fn (Builder $query) => $query
                ->where('status', OfferStatus::Accepted)
                ->whereHas('request', fn (Builder $requests) => $requests->where('status', RequestStatus::Completed))])
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->paginate(12)
            ->withQueryString();

        $skills = Skill::query()->active()->orderBy('name')->get(['id', 'name']);
        $cities = HandymanProfile::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city');

        return view('handymen.index', compact('handymen', 'skills', 'cities', 'city', 'skillId'));
    }

    public function show(HandymanProfile $handyman): View
    {
        $handyman->load(['user', 'skills.services.category']);
        abort_unless($handyman->user?->status === 'active', 404);

        $handyman->loadCount(['reviews', 'favoritedBy'])
            ->loadCount(['offers as completed_jobs_count' => fn (Builder $query) => $query
                ->where('status', OfferStatus::Accepted)
                ->whereHas('request', fn (Builder $requests) => $requests->where('status', RequestStatus::Completed))])
            ->loadAvg('reviews', 'rating');

        $reviews = $handyman->reviews()
            ->with(['offer.request.service', 'offer.request.homeowner.user'])
            ->latest('reviews.created_at')
            ->get();

        $ratingBreakdown = collect([5, 4, 3, 2, 1])->mapWithKeys(
            fn (int $stars): array => [$stars => $reviews->where('rating', $stars)->count()],
        );

        $services = $handyman->skills->flatMap->services->unique('id')->sortBy('name')->values();

        return view('handymen.show', compact('handyman', 'reviews', 'ratingBreakdown', 'services'));
    }
}
