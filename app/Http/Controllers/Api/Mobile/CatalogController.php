<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\CatalogServiceResource;
use App\Http\Resources\Mobile\HandymanCardResource;
use App\Models\Category;
use App\Models\HandymanProfile;
use App\Models\Offer;
use App\Models\Service;
use App\RequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Categories and services, with the same visibility and price references as the website catalog.
 */
class CatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('q')->toString());

        $categories = Category::query()
            ->active()
            ->with(['services' => fn ($query) => $query
                ->active()
                ->when($search !== '', fn (Builder $inner) => $inner->where(fn (Builder $match) => $match
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
                ->withCount('requests')
                ->withMin(['offers as offer_price_min' => fn (Builder $offers) => $offers->priceReference()], 'price')
                ->withMax(['offers as offer_price_max' => fn (Builder $offers) => $offers->priceReference()], 'price')
                ->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $category): bool => $category->services->isNotEmpty())
            ->values();

        return response()->json(['data' => $categories->map(fn (Category $category): array => [
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon(),
            'services' => CatalogServiceResource::collection($category->services)->resolve($request),
        ])]);
    }

    public function show(Request $request, Service $service): JsonResponse
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
            ->limit(10)
            ->get();

        $prices = Offer::query()
            ->priceReference()
            ->whereHas('request', fn (Builder $query) => $query->where('service_id', $service->id))
            ->selectRaw('min(price) as minimum, max(price) as maximum, avg(price) as average, count(*) as total')
            ->toBase()
            ->first();

        return response()->json(['data' => [
            ...CatalogServiceResource::make($service)->resolve($request),
            'open_requests' => $service->requests()->where('status', RequestStatus::Open)->count(),
            'completed_requests' => $service->requests()->where('status', RequestStatus::Completed)->count(),
            'prices' => [
                'minimum' => $prices?->minimum !== null ? (float) $prices->minimum : null,
                'maximum' => $prices?->maximum !== null ? (float) $prices->maximum : null,
                'average' => $prices?->average !== null ? round((float) $prices->average) : null,
                'offers' => (int) ($prices->total ?? 0),
            ],
            'handymen' => HandymanCardResource::collection($handymen)->resolve($request),
        ]]);
    }
}
