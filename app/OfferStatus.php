<?php

namespace App;

enum OfferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Withdrawn => 'Withdrawn',
            self::Superseded => 'Superseded',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-warning',
            self::Accepted => 'text-bg-success',
            self::Declined => 'text-bg-danger',
            self::Withdrawn => 'text-bg-secondary',
            self::Superseded => 'text-bg-light border',
        };
    }
}
