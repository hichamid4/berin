<?php

namespace App\Http\Controllers\Api\Mobile\Homeowner;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Actions\Marketplace\PhotoStorage;
use App\Actions\Messaging\ConversationProvisioner;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Homeowner\StoreServiceRequestRequest;
use App\Http\Requests\Homeowner\UpdateServiceRequestRequest;
use App\Http\Resources\Mobile\ServiceRequestResource;
use App\Models\ActivityEvent;
use App\Models\Offer;
use App\Models\RequestPhoto;
use App\Models\ServiceRequest;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * The homeowner's own requests ("My requests"): list, detail, create, edit, cancel and confirm.
 * Validation, policies and state transitions are the website's (form requests, ServiceRequestPolicy,
 * MarketplaceWorkflow). Lookups are scoped to the owner (404 otherwise).
 */
class RequestController extends Controller
{
    public function index(Request $request, ConversationProvisioner $conversations): AnonymousResourceCollection
    {
        $conversations->provisionMissingFor($request->user());
        $status = RequestStatus::tryFrom($request->string('status')->toString());

        $requests = ServiceRequest::query()
            ->where('homeowner_id', $request->user()->id)
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->with(['service.category', 'acceptedOffer.handyman.user.handymanProfile', 'acceptedOffer.conversation'])
            ->withCount(['offers', 'offers as pending_offers_count' => fn (Builder $query) => $query->where('status', OfferStatus::Pending)])
            ->latest()
            ->paginate(20);

        return ServiceRequestResource::collection($requests);
    }

    public function show(Request $request, int $serviceRequest, ConversationProvisioner $conversations): ServiceRequestResource
    {
        $conversations->provisionMissingFor($request->user());

        return ServiceRequestResource::make(self::detail($request->user()->id, $serviceRequest));
    }

    public function store(StoreServiceRequestRequest $request, PhotoStorage $photos): JsonResponse
    {
        $user = $request->user();

        $serviceRequest = DB::transaction(function () use ($request, $user): ServiceRequest {
            $serviceRequest = ServiceRequest::query()->create([
                ...$request->safe()->except('photos'),
                'homeowner_id' => $user->id,
                'status' => RequestStatus::Open,
            ]);
            ActivityEvent::record($user, 'request.created', 'service_request', $serviceRequest->id, ['title' => $serviceRequest->title]);

            return $serviceRequest;
        });

        $photos->attachToRequest($serviceRequest, $request->file('photos', []));

        return ServiceRequestResource::make(self::detail($user->id, $serviceRequest->id))->response()->setStatusCode(201);
    }

    public function update(UpdateServiceRequestRequest $request, ServiceRequest $serviceRequest, PhotoStorage $photos): ServiceRequestResource
    {
        DB::transaction(function () use ($request, $serviceRequest): void {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($serviceRequest->id);

            if (! $locked->isOpen()) {
                throw new BusinessRuleViolation('Only open requests can be edited.');
            }

            $locked->update($request->safe()->except('photos'));
            ActivityEvent::record($request->user(), 'request.updated', 'service_request', $locked->id);
        });

        $photos->attachToRequest($serviceRequest, $request->file('photos', []));

        return ServiceRequestResource::make(self::detail($request->user()->id, $serviceRequest->id));
    }

    public function cancel(Request $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow): ServiceRequestResource
    {
        Gate::authorize('cancel', $serviceRequest);
        $workflow->cancelRequest($serviceRequest, $request->user());

        return ServiceRequestResource::make(self::detail($request->user()->id, $serviceRequest->id));
    }

    public function confirmCompletion(Request $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow): ServiceRequestResource
    {
        Gate::authorize('confirmCompletion', $serviceRequest);
        $workflow->confirmCompletion($serviceRequest, $request->user());

        return ServiceRequestResource::make(self::detail($request->user()->id, $serviceRequest->id));
    }

    public function destroyPhoto(Request $request, ServiceRequest $serviceRequest, RequestPhoto $photo, PhotoStorage $photos): ServiceRequestResource
    {
        Gate::authorize('update', $serviceRequest);
        abort_unless($photo->request_id === $serviceRequest->id, 404);
        $photos->delete($photo);

        return ServiceRequestResource::make(self::detail($request->user()->id, $serviceRequest->id));
    }

    /**
     * A request with its photos, review and offers (accepted and pending first, then by price),
     * each offer able to answer the policy checks, plus each handyman's public rating.
     */
    public static function detail(int $homeownerId, int $requestId): ServiceRequest
    {
        $serviceRequest = ServiceRequest::query()
            ->where('homeowner_id', $homeownerId)
            ->with([
                'service.category',
                'photos',
                'acceptedOffer.handyman.user.handymanProfile',
                'acceptedOffer.conversation',
                'acceptedOffer.review',
                'offers' => fn ($query) => $query
                    ->where('status', '!=', OfferStatus::Withdrawn)
                    ->orderByRaw("FIELD(status, 'accepted', 'pending', 'superseded', 'declined')")
                    ->orderBy('price'),
                'offers.handyman.user.handymanProfile',
                'offers.conversation',
                'offers.photos',
            ])
            ->withCount(['offers', 'offers as pending_offers_count' => fn (Builder $query) => $query->where('status', OfferStatus::Pending)])
            ->findOrFail($requestId);

        $ratings = DB::table('reviews')
            ->join('offers', 'offers.id', '=', 'reviews.offer_id')
            ->whereIn('offers.handyman_id', $serviceRequest->offers->pluck('handyman_id'))
            ->groupBy('offers.handyman_id')
            ->selectRaw('offers.handyman_id, avg(reviews.rating) as average, count(*) as total')
            ->get()
            ->keyBy('handyman_id');

        $serviceRequest->offers->each(function (Offer $offer) use ($serviceRequest, $ratings): void {
            $offer->setRelation('request', $serviceRequest);
            $rating = $ratings->get($offer->handyman_id);
            $offer->handyman_rating = $rating ? ['average' => round((float) $rating->average, 1), 'count' => (int) $rating->total] : null;
        });

        return $serviceRequest;
    }
}
