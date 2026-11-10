<?php

namespace App\Http\Controllers\Homeowner;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $profile = $user->homeownerProfile;

        $requests = $profile->requests()
            ->with(['service.category', 'acceptedOffer.handyman.user'])
            ->withCount(['offers', 'offers as pending_offers_count' => fn (Builder $query) => $query->where('status', OfferStatus::Pending)])
            ->latest()
            ->get();

        $pendingOffers = Offer::query()
            ->where('status', OfferStatus::Pending)
            ->whereIn('request_id', $requests->pluck('id'))
            ->with(['handyman.user', 'request'])
            ->latest()
            ->limit(6)
            ->get();

        $favorites = $profile->favoriteHandymen()
            ->with(['user', 'skills'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get();

        $stats = [
            'total' => $requests->count(),
            'open' => $requests->where('status', RequestStatus::Open)->count(),
            'active' => $requests->whereIn('status', [RequestStatus::Booked, RequestStatus::CompletionPending])->count(),
            'completed' => $requests->where('status', RequestStatus::Completed)->count(),
            'offers' => $requests->sum('offers_count'),
            'favorites' => $favorites->count(),
        ];

        $awaitingConfirmation = $requests->where('status', RequestStatus::CompletionPending);

        return view('dashboards.homeowner', compact('user', 'profile', 'requests', 'pendingOffers', 'favorites', 'stats', 'awaitingConfirmation'));
    }
}
