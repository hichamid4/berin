<?php

namespace App;

enum UserRole: string
{
    case Homeowner = 'homeowner';
    case Handyman = 'handyman';
    case Administrator = 'admin';

    public function dashboardRouteName(): string
    {
        return match ($this) {
            self::Homeowner => 'homeowner.dashboard',
            self::Handyman => 'handyman.dashboard',
            self::Administrator => 'admin.dashboard',
        };
    }

    public function loginRouteName(): string
    {
        return match ($this) {
            self::Homeowner => 'login',
            self::Handyman => 'handyman.login',
            self::Administrator => 'admin.login',
        };
    }
}
