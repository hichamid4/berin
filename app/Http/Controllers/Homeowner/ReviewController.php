<?php

namespace App\Http\Controllers\Homeowner;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Homeowner\ReviewRequest;
use App\Models\Review;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow): RedirectResponse
    {
        $workflow->submitReview($serviceRequest, $request->user(), $request->integer('rating'), $request->validated('comment'));

        return to_route('homeowner.requests.show', $serviceRequest)->with('success', 'Thank you! Your review is now visible on the handyman\'s profile.');
    }

    public function update(ReviewRequest $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow): RedirectResponse
    {
        $review = Review::query()->findOrFail($serviceRequest->accepted_offer_id);
        $workflow->updateReview($review, $request->user(), $request->integer('rating'), $request->validated('comment'));

        return to_route('homeowner.requests.show', $serviceRequest)->with('success', 'Your review was updated.');
    }
}
