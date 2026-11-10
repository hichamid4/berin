<?php

namespace App\Http\Controllers\Handyman;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $profile = $user->handymanProfile->load('skills');

        $offers = $profile->offers()
            ->with(['request.service.category', 'review'])
            ->latest()
            ->get();

        $matchingRequests = ServiceRequest::query()
            ->open()
            ->matchingHandyman($profile)
            ->whereNotIn('id', $offers->pluck('request_id'))
            ->with(['service.category'])
            ->withCount('offers')
            ->latest()
            ->limit(6)
            ->get();

        $reviews = $profile->reviews()
            ->with(['offer.request.service', 'offer.request.homeowner.user'])
            ->latest('reviews.created_at')
            ->limit(3)
            ->get();

        $completedJobs = $offers->filter(fn ($offer): bool => $offer->status === OfferStatus::Accepted
            && $offer->request->status === RequestStatus::Completed);

        $stats = [
            'matching' => ServiceRequest::query()->open()->matchingHandyman($profile)->count(),
            'pending' => $offers->where('status', OfferStatus::Pending)->count(),
            'booked' => $offers->filter(fn ($offer): bool => $offer->status === OfferStatus::Accepted
                && in_array($offer->request->status, [RequestStatus::Booked, RequestStatus::CompletionPending], true))->count(),
            'completed' => $completedJobs->count(),
            'earnings' => $completedJobs->sum(fn ($offer): float => (float) $offer->price),
            'rating' => $profile->reviews()->avg('rating'),
            'reviews' => $profile->reviews()->count(),
        ];

        $activeJobs = $offers->filter(fn ($offer): bool => $offer->status === OfferStatus::Accepted
            && in_array($offer->request->status, [RequestStatus::Booked, RequestStatus::CompletionPending], true));

        return view('dashboards.handyman', compact('user', 'profile', 'offers', 'matchingRequests', 'reviews', 'stats', 'activeJobs'));
    }
}
