<?php

namespace App\Http\Controllers\Api\Mobile\Handyman;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\OpenRequestResource;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

        $requests = ServiceRequest::query()
            ->open()
            ->matchingHandyman($profile)
            ->with(['service.category'])
            ->withCount('offers')
            ->withExists(['offers as has_my_offer' => fn (Builder $query) => $query->where('handyman_id', $user->id)])
            ->latest()
            ->paginate(20);

        return OpenRequestResource::collection($requests);
    }
}
