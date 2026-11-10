<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;
use App\RequestStatus;

class OfferPolicy
{
    public function view(User $user, Offer $offer): bool
    {
        return $user->isAdministrator()
            || $offer->handyman_id === $user->id
            || $offer->request->isOwnedBy($user);
    }

    public function update(User $user, Offer $offer): bool
    {
        return $offer->handyman_id === $user->id && $offer->isPending() && $offer->request->isOpen();
    }

    public function withdraw(User $user, Offer $offer): bool
    {
        return $offer->handyman_id === $user->id && $offer->isPending();
    }

    public function respond(User $user, Offer $offer): bool
    {
        return $offer->request->isOwnedBy($user) && $offer->isPending() && $offer->request->isOpen();
    }

    public function markCompleted(User $user, Offer $offer): bool
    {
        return $offer->handyman_id === $user->id
            && $offer->request->accepted_offer_id === $offer->id
            && $offer->request->status === RequestStatus::Booked;
    }
}
