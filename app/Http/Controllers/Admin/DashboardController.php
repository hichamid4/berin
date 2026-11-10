<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\Skill;
use App\Models\User;
use App\OfferStatus;
use App\RequestStatus;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $usersByRole = User::query()->withTrashed()->selectRaw('role, status, count(*) as total')->groupBy('role', 'status')->get();
        $requestsByStatus = ServiceRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $offersByStatus = Offer::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $stats = [
            'homeowners' => $usersByRole->where('role', UserRole::Homeowner->value)->sum('total'),
            'handymen' => $usersByRole->where('role', UserRole::Handyman->value)->sum('total'),
            'blocked' => $usersByRole->where('status', 'blocked')->sum('total'),
            'anonymized' => $usersByRole->where('status', 'anonymized')->sum('total'),
            'requests' => $requestsByStatus->sum(),
            'open_requests' => $requestsByStatus->get(RequestStatus::Open->value, 0),
            'offers' => $offersByStatus->sum(),
            'pending_offers' => $offersByStatus->get(OfferStatus::Pending->value, 0),
            'reviews' => Review::query()->count(),
            'rating' => Review::query()->avg('rating'),
            'skills' => Skill::query()->count(),
            'accepted_value' => Offer::query()->where('status', OfferStatus::Accepted)->sum('price'),
        ];

        $categories = Category::query()
            ->withCount('services')
            ->withCount(['services as requests_count' => fn (Builder $query) => $query
                ->join('service_requests', 'service_requests.service_id', '=', 'services.id')])
            ->orderByDesc('requests_count')
            ->get();

        $recentActivity = ActivityEvent::query()->with('actor')->latest('created_at')->latest('id')->limit(10)->get();
        $recentUsers = User::query()->whereIn('role', [UserRole::Homeowner, UserRole::Handyman])->latest()->limit(6)->get();
        $recentRequests = ServiceRequest::query()->with(['homeowner.user', 'service.category'])->withCount('offers')->latest()->limit(6)->get();

        return view('admin.dashboard', compact('stats', 'requestsByStatus', 'offersByStatus', 'categories', 'recentActivity', 'recentUsers', 'recentRequests'));
    }
}
