<?php

namespace App\Http\Controllers\Api\Mobile\Homeowner;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Homeowner\ReviewRequest;
use App\Http\Resources\Mobile\ServiceRequestResource;
use App\Models\Review;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;

/**
 * Rate the handyman after a completed job (ReviewRequest authorizes through the request policy).
 */
class ReviewController extends Controller
{
    public function store(ReviewRequest $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow): JsonResponse
    {
        $workflow->submitReview($serviceRequest, $request->user(), $request->integer('rating'), $request->validated('comment'));

        return ServiceRequestResource::make(RequestController::detail($request->user()->id, $serviceRequest->id))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ReviewRequest $request, ServiceRequest $serviceRequest, MarketplaceWorkflow $workflow): ServiceRequestResource
    {
        $review = Review::query()->findOrFail($serviceRequest->accepted_offer_id);
        $workflow->updateReview($review, $request->user(), $request->integer('rating'), $request->validated('comment'));

        return ServiceRequestResource::make(RequestController::detail($request->user()->id, $serviceRequest->id));
    }
}
