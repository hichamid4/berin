<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HandymanProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\RequestStatus;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->active()
            ->withCount(['services' => fn (Builder $query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $popularServices = Service::query()
            ->active()
            ->with(['category', 'skills'])
            ->withCount('requests')
            ->orderByDesc('requests_count')
            ->orderBy('name')
            ->limit(6)
            ->get();

        $featuredHandymen = HandymanProfile::query()
            ->whereHas('user', fn (Builder $query) => $query->where('status', 'active'))
            ->with(['user', 'skills'])
            ->withCount(['reviews', 'favoritedBy'])
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->limit(4)
            ->get();

        $testimonials = Review::query()
            ->whereNotNull('comment')
            ->where('rating', '>=', 4)
            ->with(['offer.handyman.user', 'offer.request.service', 'offer.request.homeowner.user'])
            ->latest()
            ->limit(3)
            ->get();

        $stats = [
            'handymen' => User::query()->where('role', UserRole::Handyman)->where('status', 'active')->count(),
            'services' => Service::query()->active()->count(),
            'completed' => ServiceRequest::query()->where('status', RequestStatus::Completed)->count(),
        ];

        return view('home', compact('categories', 'popularServices', 'featuredHandymen', 'testimonials', 'stats'));
    }
}
