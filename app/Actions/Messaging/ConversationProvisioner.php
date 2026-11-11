<?php

namespace App\Actions\Messaging;

use App\Exceptions\BusinessRuleViolation;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\OfferStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Creates the one conversation that belongs to an accepted offer.
 *
 * Only the offer a request actually selected qualifies, never pending, declined, withdrawn or
 * superseded offers. Creation is idempotent: the unique offer_id index is the final guard.
 */
class ConversationProvisioner
{
    /**
     * Open (or return) the conversation of a just-accepted offer. Called inside the acceptance
     * transaction after the request row was locked, so the conversation commits with the booking.
     */
    public function openForAcceptedOffer(Offer $offer, ServiceRequest $request): Conversation
    {
        if ($offer->status !== OfferStatus::Accepted || $request->accepted_offer_id !== $offer->id || $offer->request_id !== $request->id) {
            throw new BusinessRuleViolation('A conversation can only be opened for the offer a request accepted.');
        }

        return Conversation::query()->firstOrCreate(['offer_id' => $offer->id], [
            'request_id' => $request->id,
            'homeowner_id' => $request->homeowner_id,
            'handyman_id' => $offer->handyman_id,
            'last_activity_at' => $offer->accepted_at ?? now(),
        ]);
    }

    /**
     * Safety net for accepted offers that reached the database without the acceptance workflow
     * (for example imported or seeded data): give each of the user's booked jobs its conversation.
     *
     * @return int Number of conversations created.
     */
    public function provisionMissingFor(User $user): int
    {
        if (! $user->canUseMobileApp()) {
            return 0;
        }

        $now = now()->format('Y-m-d H:i:s.u');

        $missing = DB::table('offers')
            ->join('service_requests', fn (JoinClause $join) => $join
                ->on('service_requests.id', '=', 'offers.request_id')
                ->on('service_requests.accepted_offer_id', '=', 'offers.id'))
            ->where('offers.status', OfferStatus::Accepted->value)
            ->when(
                $user->isHomeowner(),
                fn (Builder $query) => $query->where('service_requests.homeowner_id', $user->id),
                fn (Builder $query) => $query->where('offers.handyman_id', $user->id),
            )
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('conversations')->whereColumn('conversations.offer_id', 'offers.id'))
            ->select(['offers.id', 'offers.request_id', 'service_requests.homeowner_id', 'offers.handyman_id'])
            ->selectRaw('COALESCE(offers.accepted_at, ?)', [$now])
            ->selectRaw('?', [$now])
            ->selectRaw('?', [$now]);

        return DB::table('conversations')->insertOrIgnoreUsing(
            ['offer_id', 'request_id', 'homeowner_id', 'handyman_id', 'last_activity_at', 'created_at', 'updated_at'],
            $missing,
        );
    }
}
