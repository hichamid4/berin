<?php

namespace App\Http\Resources\Mobile;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * The signed-in member's own profile, including their private contact details.
 *
 * @mixin User
 */
class ProfileResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->isHandyman() ? $this->handymanProfile : $this->homeownerProfile;
        $photoPath = $this->isHandyman() ? $this->handymanProfile?->photo_path : null;

        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->fullName(),
            'initials' => $this->initials(),
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone_e164,
            'city' => $profile?->city,
            'address' => $profile?->address,
            'skills' => $this->isHandyman() && $profile?->relationLoaded('skills')
                ? $profile->skills->map(fn ($skill): array => ['id' => $skill->id, 'name' => $skill->name])->values()
                : [],
            'photo_url' => $photoPath !== null ? route('handymen.photo', $this->id) : null,
            'email_verified' => $this->hasVerifiedEmail(),
            'member_since' => self::timestamp($this->created_at),
        ];
    }
}
