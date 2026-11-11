<?php

namespace App\Http\Resources\Mobile;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Public face of another member: name, role and profile photo only. Never email or phone.
 *
 * @mixin User
 */
class ParticipantResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAvailable = $this->status === 'active' && ! $this->trashed();
        $hasPhoto = $isAvailable && $this->isHandyman() && $this->relationLoaded('handymanProfile') && $this->handymanProfile?->photo_path !== null;

        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'first_name' => $isAvailable ? $this->first_name : 'Former',
            'name' => $isAvailable ? $this->fullName() : 'Former member',
            'initials' => $isAvailable ? $this->initials() : 'FM',
            'photo_url' => $hasPhoto ? route('handymen.photo', $this->id) : null,
            'is_available' => $isAvailable,
        ];
    }
}
