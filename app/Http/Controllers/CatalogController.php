<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogFilterRequest;
use App\Models\Category;
use App\Models\HandymanProfile;
use App\Models\Offer;
use App\Models\Service;
use App\RequestStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CatalogController extends Controller
{
    /**
     * Browse active services. The optional price range keeps only services that have received at
     * least one live or accepted offer within it; services have no fixed price of their own.
     */
    public function index(CatalogFilterRequest $request): View
    {
        $categories = Category::query()
            ->active()
            ->withCount(['services' => fn (Builder $query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $selectedCategory = $request->categoryId();
        $search = $request->search();
        $minimumPrice = $request->minimumPrice();
        $maximumPrice = $request->maximumPrice();
        $isPriceFiltered = $minimumPrice !== null || $maximumPrice !== null;

        $services = Service::query()
            ->active()
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->with(['category', 'skills'])
            ->withCount('requests')
            ->withMin(['offers as offer_price_min' => fn (Builder $query) => $query->priceReference()], 'price')
            ->withMax(['offers as offer_price_max' => fn (Builder $query) => $query->priceReference()], 'price')
            ->when($selectedCategory, fn (Builder $query) => $query->where('category_id', $selectedCategory))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($isPriceFiltered, fn (Builder $query) => $query->whereHas('offers', fn (Builder $offers) => $offers
                ->priceReference()
                ->when($minimumPrice !== null, fn (Builder $inner) => $inner->where('offers.price', '>=', $minimumPrice))
                ->when($maximumPrice !== null, fn (Builder $inner) => $inner->where('offers.price', '<=', $maximumPrice))))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $marketPrices = Offer::query()
            ->priceReference()
            ->whereHas('request.service', fn (Builder $query) => $query->active()->whereHas('category', fn (Builder $inner) => $inner->where('is_active', true)))
            ->selectRaw('min(price) as minimum, max(price) as maximum')
            ->first();

        return view('catalog.index', compact('categories', 'services', 'selectedCategory', 'search', 'minimumPrice', 'maximumPrice', 'isPriceFiltered', 'marketPrices'));
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
            ->priceReference()
            ->whereHas('request', fn (Builder $query) => $query->where('service_id', $service->id))
            ->selectRaw('min(price) as minimum, max(price) as maximum, avg(price) as average, count(*) as total')
            ->first();

        $openRequestCount = $service->requests()->where('status', RequestStatus::Open)->count();
        $completedRequestCount = $service->requests()->where('status', RequestStatus::Completed)->count();

        return view('catalog.show', compact('service', 'handymen', 'relatedServices', 'openRequestCount', 'completedRequestCount', 'offerPrices'));
    }
}
