<?php

namespace App\Http\Controllers\Handyman;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Actions\Marketplace\PhotoStorage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Handyman\OfferRequest;
use App\Models\Offer;
use App\Models\OfferPhoto;
use App\Models\ServiceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OfferController extends Controller
{
    public function store(OfferRequest $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow, PhotoStorage $photos): RedirectResponse
    {
        $offer = $workflow->submitOffer($serviceRequest, $request->user()->handymanProfile, $request->safe()->except('photos'));
        $photos->attachToOffer($offer, $request->file('photos', []));

        return to_route('handyman.requests.show', $serviceRequest)
            ->with('success', 'Your offer was sent. The homeowner will be able to compare it with other offers.');
    }

    public function edit(Request $request, Offer $offer): View
    {
        Gate::authorize('update', $offer);

        return view('handyman.offers.edit', [
            'user' => $request->user(),
            'offer' => $offer->load(['request.service.category', 'photos']),
        ]);
    }

    public function update(OfferRequest $request, Offer $offer, MarketplaceWorkflow $workflow, PhotoStorage $photos): RedirectResponse
    {
        $workflow->updateOffer($offer, $request->safe()->except('photos'), $request->user());
        $photos->attachToOffer($offer, $request->file('photos', []));

        return to_route('handyman.requests.show', $offer->request_id)->with('success', 'Your offer was updated.');
    }

    public function withdraw(Request $request, Offer $offer, MarketplaceWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('withdraw', $offer);
        $workflow->withdrawOffer($offer, $request->user());

        return to_route('handyman.requests.show', $offer->request_id)->with('success', 'Your offer was withdrawn.');
    }

    public function markCompleted(Request $request, Offer $offer, MarketplaceWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('markCompleted', $offer);
        $workflow->markJobCompleted($offer, $request->user());

        return back()->with('success', 'The job was marked as completed. The homeowner has been asked to confirm.');
    }

    public function destroyPhoto(Offer $offer, OfferPhoto $photo, PhotoStorage $photos): RedirectResponse
    {
        Gate::authorize('update', $offer);
        abort_unless($photo->offer_id === $offer->id, 404);

        $photos->delete($photo);

        return back()->with('success', 'The photo was removed.');
    }
}
