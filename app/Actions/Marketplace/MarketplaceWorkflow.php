<?php

namespace App\Actions\Marketplace;

use App\Exceptions\BusinessRuleViolation;
use App\Models\ActivityEvent;
use App\Models\HandymanProfile;
use App\Models\Offer;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Support\Facades\DB;

/**
 * State transitions of the approved request/offer/review lifecycle.
 *
 * Each transition locks the service request row first, re-validates the state that the
 * policy checked before the transaction, and only then writes. The database constraints
 * (one accepted offer per request, lifecycle CHECKs, review primary key) are the final guard.
 */
class MarketplaceWorkflow
{
    /**
     * @param  array{price: numeric-string|float, start_date: string, end_date: string, description: string}  $data
     */
    public function submitOffer(ServiceRequest $request, HandymanProfile $handyman, array $data): Offer
    {
        return DB::transaction(function () use ($request, $handyman, $data): Offer {
            $locked = $this->lockRequest($request);

            if (! $locked->isOpen()) {
                throw new BusinessRuleViolation('This request is no longer open for offers.');
            }

            if (! $locked->matchesHandyman($handyman)) {
                throw new BusinessRuleViolation('You do not hold a skill required by this service.');
            }

            if ($locked->hasActiveOfferFrom($handyman)) {
                throw new BusinessRuleViolation('You already have an active offer on this request. Edit or withdraw it instead.');
            }

            $offer = $locked->offers()->create([...$data, 'handyman_id' => $handyman->user_id, 'currency' => 'MAD', 'status' => OfferStatus::Pending]);
            ActivityEvent::record($handyman->user, 'offer.submitted', 'offer', $offer->id, ['price' => $offer->price, 'currency' => 'MAD']);

            return $offer;
        });
    }

    /**
     * @param  array{price: numeric-string|float, start_date: string, end_date: string, description: string}  $data
     */
    public function updateOffer(Offer $offer, array $data, User $actor): void
    {
        DB::transaction(function () use ($offer, $data, $actor): void {
            $request = $this->lockRequest($offer->request);
            $locked = Offer::query()->lockForUpdate()->findOrFail($offer->id);

            if (! $request->isOpen() || ! $locked->isPending()) {
                throw new BusinessRuleViolation('Only pending offers on open requests can be edited.');
            }

            $locked->update($data);
            ActivityEvent::record($actor, 'offer.updated', 'offer', $locked->id, ['price' => $locked->price, 'currency' => 'MAD']);
        });
    }

    public function withdrawOffer(Offer $offer, User $actor): void
    {
        DB::transaction(function () use ($offer, $actor): void {
            $this->lockRequest($offer->request);
            $locked = Offer::query()->lockForUpdate()->findOrFail($offer->id);

            if (! $locked->isPending()) {
                throw new BusinessRuleViolation('Only pending offers can be withdrawn. This offer is '.$locked->status->label().'.');
            }

            $locked->update(['status' => OfferStatus::Withdrawn]);
            ActivityEvent::record($actor, 'offer.withdrawn', 'offer', $locked->id);
        });
    }

    public function acceptOffer(Offer $offer, User $homeowner): void
    {
        DB::transaction(function () use ($offer, $homeowner): void {
            $request = $this->lockRequest($offer->request);
            $locked = Offer::query()->lockForUpdate()->findOrFail($offer->id);

            if (! $request->isOwnedBy($homeowner)) {
                throw new BusinessRuleViolation('Only the owner of the request can accept an offer.');
            }

            if (! $request->isOpen()) {
                throw new BusinessRuleViolation('An offer has already been accepted for this request, or it is no longer open.');
            }

            if ($locked->request_id !== $request->id || ! $locked->isPending()) {
                throw new BusinessRuleViolation('This offer is no longer pending and cannot be accepted.');
            }

            $locked->update(['status' => OfferStatus::Accepted, 'accepted_at' => now()]);
            $superseded = $request->offers()->whereKeyNot($locked->id)->where('status', OfferStatus::Pending)
                ->update(['status' => OfferStatus::Superseded, 'updated_at' => now()]);
            $request->forceFill(['status' => RequestStatus::Booked, 'accepted_offer_id' => $locked->id])->save();

            ActivityEvent::record($homeowner, 'offer.accepted', 'offer', $locked->id, ['superseded' => $superseded]);
        });
    }

    public function declineOffer(Offer $offer, User $homeowner): void
    {
        DB::transaction(function () use ($offer, $homeowner): void {
            $request = $this->lockRequest($offer->request);
            $locked = Offer::query()->lockForUpdate()->findOrFail($offer->id);

            if (! $request->isOwnedBy($homeowner) || ! $request->isOpen() || ! $locked->isPending()) {
                throw new BusinessRuleViolation('Only pending offers on your open requests can be declined.');
            }

            $locked->update(['status' => OfferStatus::Declined]);
            ActivityEvent::record($homeowner, 'offer.declined', 'offer', $locked->id);
        });
    }

    public function cancelRequest(ServiceRequest $request, User $homeowner): void
    {
        DB::transaction(function () use ($request, $homeowner): void {
            $locked = $this->lockRequest($request);

            if (! $locked->isOwnedBy($homeowner)) {
                throw new BusinessRuleViolation('Only the owner of the request can cancel it.');
            }

            if (! $locked->isOpen()) {
                throw new BusinessRuleViolation('A request can only be cancelled before an offer is accepted. This request is '.$locked->status->label().'.');
            }

            $declined = $locked->offers()->where('status', OfferStatus::Pending)->update(['status' => OfferStatus::Declined, 'updated_at' => now()]);
            $locked->forceFill(['status' => RequestStatus::Cancelled, 'cancelled_at' => now()])->save();

            ActivityEvent::record($homeowner, 'request.cancelled', 'service_request', $locked->id, ['offers_declined' => $declined]);
        });
    }

    public function markJobCompleted(Offer $offer, User $handyman): void
    {
        DB::transaction(function () use ($offer, $handyman): void {
            $request = $this->lockRequest($offer->request);

            if ($offer->handyman_id !== $handyman->id || $request->accepted_offer_id !== $offer->id) {
                throw new BusinessRuleViolation('Only the handyman whose offer was accepted can mark this job as completed.');
            }

            if ($request->status !== RequestStatus::Booked) {
                throw new BusinessRuleViolation('This job is not in the booked state. It is '.$request->status->label().'.');
            }

            $request->forceFill(['status' => RequestStatus::CompletionPending, 'handyman_completed_at' => now()])->save();
            ActivityEvent::record($handyman, 'job.marked_complete', 'service_request', $request->id);
        });
    }

    public function confirmCompletion(ServiceRequest $request, User $homeowner): void
    {
        DB::transaction(function () use ($request, $homeowner): void {
            $locked = $this->lockRequest($request);

            if (! $locked->isOwnedBy($homeowner)) {
                throw new BusinessRuleViolation('Only the owner of the request can confirm completion.');
            }

            if ($locked->status !== RequestStatus::CompletionPending) {
                throw new BusinessRuleViolation('Completion can only be confirmed after the handyman marks the job as done.');
            }

            $locked->forceFill(['status' => RequestStatus::Completed, 'homeowner_confirmed_at' => now()])->save();
            ActivityEvent::record($homeowner, 'request.completed', 'service_request', $locked->id);
        });
    }

    public function submitReview(ServiceRequest $request, User $homeowner, int $rating, ?string $comment): Review
    {
        return DB::transaction(function () use ($request, $homeowner, $rating, $comment): Review {
            $locked = $this->lockRequest($request);

            if (! $locked->isOwnedBy($homeowner) || $locked->status !== RequestStatus::Completed || $locked->accepted_offer_id === null) {
                throw new BusinessRuleViolation('A review can only be left by the homeowner after the job is completed and confirmed.');
            }

            $offer = Offer::query()->lockForUpdate()->findOrFail($locked->accepted_offer_id);

            if ($offer->status !== OfferStatus::Accepted || Review::query()->whereKey($offer->id)->exists()) {
                throw new BusinessRuleViolation('This job has already been reviewed. You can edit your existing review instead.');
            }

            $review = Review::query()->create(['offer_id' => $offer->id, 'rating' => $rating, 'comment' => $comment]);
            ActivityEvent::record($homeowner, 'review.submitted', 'review', $offer->id, ['rating' => $rating]);

            return $review;
        });
    }

    public function updateReview(Review $review, User $homeowner, int $rating, ?string $comment): void
    {
        DB::transaction(function () use ($review, $homeowner, $rating, $comment): void {
            $locked = Review::query()->lockForUpdate()->findOrFail($review->offer_id);
            $locked->update(['rating' => $rating, 'comment' => $comment]);
            ActivityEvent::record($homeowner, 'review.updated', 'review', $locked->offer_id, ['rating' => $rating]);
        });
    }

    private function lockRequest(ServiceRequest $request): ServiceRequest
    {
        return ServiceRequest::query()->with('service')->lockForUpdate()->findOrFail($request->id);
    }
}
