<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HandymanProfile;
use App\Models\Offer;
use App\Models\Service;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->active()
            ->withCount(['services' => fn (Builder $query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $selectedCategory = $request->integer('category') ?: null;
        $search = trim($request->string('q')->toString());

        $services = Service::query()
            ->active()
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->with(['category', 'skills'])
            ->withCount('requests')
            ->when($selectedCategory, fn (Builder $query) => $query->where('category_id', $selectedCategory))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('catalog.index', compact('categories', 'services', 'selectedCategory', 'search'));
    }

    public function show(Service $service): View
    {
        $service->load(['category', 'skills']);
        abort_unless($service->is_active && $service->category->is_active, 404);

        $handymen = HandymanProfile::query()
            ->whereHas('user', fn (Builder $query) => $query->where('status', 'active'))
            ->whereHas('skills', fn (Builder $query) => $query->whereIn('skills.id', $service->skills->pluck('id')))
            ->with(['user', 'skills'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_avg_rating')
            ->limit(6)
            ->get();

        $relatedServices = Service::query()
            ->active()
            ->where('category_id', $service->category_id)
            ->whereKeyNot($service->id)
            ->with(['category', 'skills'])
            ->withCount('requests')
            ->limit(3)
            ->get();

        $offerPrices = Offer::query()
            ->whereIn('status', [OfferStatus::Pending, OfferStatus::Accepted])
            ->whereHas('request', fn (Builder $query) => $query->where('service_id', $service->id))
            ->selectRaw('min(price) as minimum, max(price) as maximum, avg(price) as average, count(*) as total')
            ->first();

        $openRequestCount = $service->requests()->where('status', RequestStatus::Open)->count();
        $completedRequestCount = $service->requests()->where('status', RequestStatus::Completed)->count();

        return view('catalog.show', compact('service', 'handymen', 'relatedServices', 'openRequestCount', 'completedRequestCount', 'offerPrices'));
    }
}
