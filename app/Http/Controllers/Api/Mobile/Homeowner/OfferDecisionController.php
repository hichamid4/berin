<?php

namespace App\Http\Controllers\Api\Mobile\Homeowner;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Actions\Messaging\ConversationProvisioner;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ServiceRequestResource;
use App\Models\Offer;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Accept or decline an offer from the app, through the same policy and workflow as the website.
 *
 * The website workflow is reused unchanged. On the app, the acceptance and the opening of the
 * in-app conversation run inside one outer transaction, so both commit together or not at all.
 * Offers accepted on the website get their conversation from ConversationProvisioner the first
 * time either participant opens the app.
 */
class OfferDecisionController extends Controller
{
    public function accept(Request $request, Offer $offer, MarketplaceWorkflow $workflow, ConversationProvisioner $conversations): ServiceRequestResource
    {
        Gate::authorize('respond', $offer);

        DB::transaction(function () use ($request, $offer, $workflow, $conversations): void {
            $workflow->acceptOffer($offer, $request->user());

            $booked = ServiceRequest::query()->lockForUpdate()->findOrFail($offer->request_id);
            $conversations->openForAcceptedOffer($offer->refresh(), $booked);
        });

        return ServiceRequestResource::make(RequestController::detail($request->user()->id, $offer->request_id));
    }

    public function decline(Request $request, Offer $offer, MarketplaceWorkflow $workflow): ServiceRequestResource
    {
        Gate::authorize('respond', $offer);
        $workflow->declineOffer($offer, $request->user());

        return ServiceRequestResource::make(RequestController::detail($request->user()->id, $offer->request_id));
    }
}
