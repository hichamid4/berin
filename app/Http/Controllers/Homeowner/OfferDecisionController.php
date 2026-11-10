<?php

namespace App\Http\Controllers\Homeowner;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OfferDecisionController extends Controller
{
    public function accept(Request $request, Offer $offer, MarketplaceWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('respond', $offer);
        $workflow->acceptOffer($offer, $request->user());

        return to_route('homeowner.requests.show', $offer->request_id)
            ->with('success', 'Offer accepted. Other pending offers were closed and you can now contact the handyman on WhatsApp.');
    }

    public function decline(Request $request, Offer $offer, MarketplaceWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('respond', $offer);
        $workflow->declineOffer($offer, $request->user());

        return to_route('homeowner.requests.show', $offer->request_id)->with('success', 'The offer was declined.');
    }
}
