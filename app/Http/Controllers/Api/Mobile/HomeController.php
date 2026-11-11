<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\Messaging\ConversationMessaging;
use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Role-aware figures for the app's Home tab, computed like the website dashboards.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, ConversationMessaging $messaging): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'role' => $user->role->value,
            'first_name' => $user->first_name,
            'stats' => $user->isHomeowner() ? $this->homeownerStats($user) : $this->handymanStats($user),
            'unread' => $messaging->unreadSummary($user),
        ]]);
    }

    /**
     * @return array<string, int>
     */
    private function homeownerStats(User $user): array
    {
        $requests = ServiceRequest::query()->where('homeowner_id', $user->id);

        return [
            'open_requests' => (clone $requests)->where('status', RequestStatus::Open)->count(),
            'active_jobs' => (clone $requests)->whereIn('status', [RequestStatus::Booked, RequestStatus::CompletionPending])->count(),
            'completed_jobs' => (clone $requests)->where('status', RequestStatus::Completed)->count(),
            'offers_to_review' => Offer::query()
                ->where('status', OfferStatus::Pending)
                ->whereHas('request', fn (Builder $query) => $query->where('homeowner_id', $user->id)->where('status', RequestStatus::Open))
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function handymanStats(User $user): array
    {
        $profile = $user->handymanProfile;
        $offers = Offer::query()->where('handyman_id', $user->id);
        $accepted = (clone $offers)->where('status', OfferStatus::Accepted);

        return [
            'matching_requests' => $profile ? ServiceRequest::query()->open()->matchingHandyman($profile)->count() : 0,
            'pending_offers' => (clone $offers)->where('status', OfferStatus::Pending)->count(),
            'active_jobs' => (clone $accepted)->whereHas('request', fn (Builder $query) => $query->whereIn('status', [RequestStatus::Booked, RequestStatus::CompletionPending]))->count(),
            'completed_jobs' => (clone $accepted)->whereHas('request', fn (Builder $query) => $query->where('status', RequestStatus::Completed))->count(),
        ];
    }
}
