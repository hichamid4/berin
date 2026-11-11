<?php

namespace App\Http\Controllers\Api\Mobile\Homeowner;

use App\Actions\Messaging\ConversationProvisioner;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ServiceRequestResource;
use App\Models\Offer;
use App\Models\ServiceRequest;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The homeowner's own requests ("My requests"). Lookups are scoped to the owner (404 otherwise).
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

    /**
     * A request with its offers, pending ones first, each able to answer the policy checks.
     */
    public static function detail(int $homeownerId, int $requestId): ServiceRequest
    {
        $serviceRequest = ServiceRequest::query()
            ->where('homeowner_id', $homeownerId)
            ->with([
                'service.category',
                'acceptedOffer.handyman.user.handymanProfile',
                'acceptedOffer.conversation',
                'offers' => fn ($query) => $query
                    ->where('status', '!=', OfferStatus::Withdrawn)
                    ->orderByRaw('status = ? DESC', [OfferStatus::Pending->value])
                    ->latest(),
                'offers.handyman.user.handymanProfile',
                'offers.conversation',
            ])
            ->withCount(['offers', 'offers as pending_offers_count' => fn (Builder $query) => $query->where('status', OfferStatus::Pending)])
            ->findOrFail($requestId);

        $serviceRequest->offers->each(fn (Offer $offer) => $offer->setRelation('request', $serviceRequest));

        return $serviceRequest;
    }
}
