<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;
use App\RequestStatus;

class ServiceRequestPolicy
{
    /**
     * Owners and administrators always see a request; handymen see open requests that match
     * their skills, and any request on which they have made an offer.
     */
    public function view(User $user, ServiceRequest $request): bool
    {
        if ($user->isAdministrator() || $request->isOwnedBy($user)) {
            return true;
        }

        if (! $user->isHandyman() || $user->handymanProfile === null) {
            return false;
        }

        return $request->offers()->where('handyman_id', $user->id)->exists()
            || ($request->isOpen() && $request->matchesHandyman($user->handymanProfile));
    }

    /**
     * The exact service address is private to the owner, administrators and the booked handyman.
     */
    public function viewPrivateLocation(User $user, ServiceRequest $request): bool
    {
        return $user->isAdministrator()
            || $request->isOwnedBy($user)
            || ($request->acceptedOffer !== null && $request->acceptedOffer->handyman_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->isHomeowner();
    }

    public function update(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedBy($user) && $request->isOpen();
    }

    public function cancel(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedBy($user) && $request->isOpen();
    }

    public function confirmCompletion(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedBy($user) && $request->status === RequestStatus::CompletionPending;
    }

    public function review(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedBy($user) && $request->status === RequestStatus::Completed;
    }

    public function makeOffer(User $user, ServiceRequest $request): bool
    {
        return $user->isHandyman()
            && $user->handymanProfile !== null
            && $request->isOpen()
            && $request->matchesHandyman($user->handymanProfile)
            && ! $request->hasActiveOfferFrom($user->handymanProfile);
    }
}
