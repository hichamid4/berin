<?php

namespace App;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Read => 'Read',
            self::Resolved => 'Resolved',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::New => 'text-bg-primary',
            self::Read => 'text-bg-info',
            self::Resolved => 'text-bg-success',
        };
    }
}
