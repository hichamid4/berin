<?php

namespace App\Http\Controllers\Api\Mobile\Handyman;

use App\Actions\Messaging\ConversationProvisioner;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\OfferResource;
use App\Models\Offer;
use App\OfferStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The handyman's own offers ("My offers").
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
}
