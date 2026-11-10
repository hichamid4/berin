<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role', 'first_name', 'last_name', 'username', 'email', 'phone_e164', 'password'])]
#[Hidden(['email', 'phone_e164', 'password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'blocked_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function homeownerProfile(): HasOne
    {
        return $this->hasOne(HomeownerProfile::class);
    }

    public function handymanProfile(): HasOne
    {
        return $this->hasOne(HandymanProfile::class);
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function dashboardRouteName(): string
    {
        return $this->role->dashboardRouteName();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->deleted_at === null;
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function isHomeowner(): bool
    {
        return $this->role === UserRole::Homeowner;
    }

    public function isHandyman(): bool
    {
        return $this->role === UserRole::Handyman;
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function shortName(): string
    {
        return trim($this->first_name.' '.mb_substr($this->last_name, 0, 1).'.');
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    /**
     * WhatsApp click-to-chat link for the user's E.164 phone number, if one is stored.
     */
    public function whatsappUrl(?string $message = null): ?string
    {
        if ($this->phone_e164 === null) {
            return null;
        }

        $url = 'https://wa.me/'.ltrim($this->phone_e164, '+');

        return $message === null ? $url : $url.'?text='.rawurlencode($message);
    }
}
