<?php

namespace App\Http\Resources\Mobile;

use App\Models\HandymanProfile;
use Illuminate\Http\Request;

/**
 * Public handyman profile as shown in the directory: no email, phone or street address.
 *
 * @mixin HandymanProfile
 */
class HandymanCardResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->user_id,
            'name' => $user->fullName(),
            'first_name' => $user->first_name,
            'initials' => $user->initials(),
            'city' => $this->city,
            'photo_url' => $this->photo_path !== null ? route('handymen.photo', $this->user_id) : null,
            'skills' => $this->relationLoaded('skills') ? $this->skills->pluck('name')->values() : [],
            'rating' => $this->reviews_avg_rating !== null ? round((float) $this->reviews_avg_rating, 1) : null,
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'completed_jobs' => (int) ($this->completed_jobs_count ?? 0),
            'member_since' => self::timestamp($user->created_at),
            'is_favorite' => (bool) ($this->is_favorite ?? false),
        ];
    }
}
