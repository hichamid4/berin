<?php

namespace App\Http\Controllers\Handyman;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ServiceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RequestBrowserController extends Controller
{
    /**
     * Open requests restricted server-side to services that need one of the handyman's skills.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->handymanProfile->load('skills');
        $categoryId = $request->integer('category') ?: null;
        $serviceId = $request->integer('service') ?: null;
        $city = trim($request->string('city')->toString());
        $search = trim($request->string('q')->toString());

        $requests = ServiceRequest::query()
            ->open()
            ->matchingHandyman($profile)
            ->when($categoryId, fn (Builder $query) => $query->whereHas('service', fn (Builder $service) => $service->where('category_id', $categoryId)))
            ->when($serviceId, fn (Builder $query) => $query->where('service_id', $serviceId))
            ->when($city !== '', fn (Builder $query) => $query->where('service_city', 'like', "%{$city}%"))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
            ->with(['service.category'])
            ->withCount('offers')
            ->withExists(['offers as has_my_offer' => fn (Builder $query) => $query->where('handyman_id', $user->id)])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $skillIds = $profile->skills->pluck('id');
        $categories = Category::query()
            ->with(['services' => fn ($query) => $query->whereHas('skills', fn (Builder $skills) => $skills->whereIn('skills.id', $skillIds))->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $category): bool => $category->services->isNotEmpty())
            ->values();

        $cities = ServiceRequest::query()->open()->matchingHandyman($profile)->distinct()->orderBy('service_city')->pluck('service_city');

        return view('handyman.requests.index', compact('user', 'profile', 'requests', 'categories', 'cities', 'categoryId', 'serviceId', 'city', 'search'));
    }

    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        Gate::authorize('view', $serviceRequest);
        $user = $request->user();

        $serviceRequest->load(['service.category', 'service.skills', 'photos', 'homeowner.user', 'acceptedOffer'])->loadCount('offers');

        $myOffers = $serviceRequest->offers()->where('handyman_id', $user->id)->with(['photos', 'review'])->latest()->get();

        return view('handyman.requests.show', [
            'user' => $user,
            'serviceRequest' => $serviceRequest,
            'myOffers' => $myOffers,
            'canOffer' => $user->can('makeOffer', $serviceRequest),
            'canSeeLocation' => $user->can('viewPrivateLocation', $serviceRequest),
        ]);
    }
}
