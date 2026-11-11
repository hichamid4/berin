<?php

namespace App\Http\Controllers\Api\Mobile\Handyman;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\HandymanRequestResource;
use App\Http\Resources\Mobile\OpenRequestResource;
use App\Models\Offer;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * "Find work": open requests restricted server-side to the handyman's skills, as on the website.
 */
class WorkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $profile = $user->handymanProfile;
        abort_if($profile === null, 404);
        $search = trim($request->string('q')->toString());
        $city = trim($request->string('city')->toString());

        $requests = ServiceRequest::query()
            ->open()
            ->matchingHandyman($profile)
            ->when($city !== '', fn (Builder $query) => $query->where('service_city', 'like', "%{$city}%"))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
            ->with(['service.category'])
            ->withCount('offers')
            ->withExists(['offers as has_my_offer' => fn (Builder $query) => $query->where('handyman_id', $user->id)])
            ->latest()
            ->paginate(20);

        return OpenRequestResource::collection($requests);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): HandymanRequestResource
    {
        Gate::authorize('view', $serviceRequest);

        return HandymanRequestResource::make(self::detail($request, $serviceRequest->id));
    }

    public static function detail(Request $request, int $requestId): ServiceRequest
    {
        $user = $request->user();
        $serviceRequest = ServiceRequest::query()
            ->with(['service.category', 'service.skills', 'photos', 'homeowner.user', 'acceptedOffer'])
            ->withCount('offers')
            ->findOrFail($requestId);

        $myOffers = Offer::query()
            ->where('request_id', $serviceRequest->id)
            ->where('handyman_id', $user->id)
            ->with(['photos', 'conversation'])
            ->latest()
            ->get()
            ->each(fn (Offer $offer) => $offer->setRelation('request', $serviceRequest));

        return $serviceRequest->setRelation('myOffers', $myOffers);
    }
}
