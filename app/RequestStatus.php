<?php

namespace App;

enum RequestStatus: string
{
    case Open = 'open';
    case Booked = 'booked';
    case CompletionPending = 'completion_pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Booked => 'Booked',
            self::CompletionPending => 'Awaiting confirmation',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'text-bg-primary',
            self::Booked => 'text-bg-info',
            self::CompletionPending => 'text-bg-warning',
            self::Completed => 'text-bg-success',
            self::Cancelled => 'text-bg-secondary',
        };
    }
}
