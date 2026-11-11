<?php

namespace App\Http\Controllers\Api\Mobile\Handyman;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Actions\Marketplace\PhotoStorage;
use App\Actions\Messaging\ConversationProvisioner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Handyman\OfferRequest;
use App\Http\Resources\Mobile\HandymanRequestResource;
use App\Http\Resources\Mobile\OfferResource;
use App\Models\Offer;
use App\Models\ServiceRequest;
use App\OfferStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * The handyman's offers ("My offers"): list, submit, edit, withdraw and mark the job completed,
 * through the website's OfferRequest, OfferPolicy and MarketplaceWorkflow.
 */
class OfferController extends Controller
{
    public function index(Request $request, ConversationProvisioner $conversations): AnonymousResourceCollection
    {
        $conversations->provisionMissingFor($request->user());
        $status = OfferStatus::tryFrom($request->string('status')->toString());

        $offers = Offer::query()
            ->where('handyman_id', $request->user()->id)
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->with(['request.service', 'conversation'])
            ->latest()
            ->paginate(20);

        return OfferResource::collection($offers);
    }

    public function store(OfferRequest $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow, PhotoStorage $photos): JsonResponse
    {
        $offer = $workflow->submitOffer($serviceRequest, $request->user()->handymanProfile, $request->safe()->except('photos'));
        $photos->attachToOffer($offer, $request->file('photos', []));

        return HandymanRequestResource::make(WorkController::detail($request, $serviceRequest->id))->response()->setStatusCode(201);
    }

    public function update(OfferRequest $request, Offer $offer, MarketplaceWorkflow $workflow, PhotoStorage $photos): HandymanRequestResource
    {
        $workflow->updateOffer($offer, $request->safe()->except('photos'), $request->user());
        $photos->attachToOffer($offer, $request->file('photos', []));

        return HandymanRequestResource::make(WorkController::detail($request, $offer->request_id));
    }

    public function withdraw(Request $request, Offer $offer, MarketplaceWorkflow $workflow): HandymanRequestResource
    {
        Gate::authorize('withdraw', $offer);
        $workflow->withdrawOffer($offer, $request->user());

        return HandymanRequestResource::make(WorkController::detail($request, $offer->request_id));
    }

    public function markCompleted(Request $request, Offer $offer, MarketplaceWorkflow $workflow): HandymanRequestResource
    {
        Gate::authorize('markCompleted', $offer);
        $workflow->markJobCompleted($offer, $request->user());

        return HandymanRequestResource::make(WorkController::detail($request, $offer->request_id));
    }
}
