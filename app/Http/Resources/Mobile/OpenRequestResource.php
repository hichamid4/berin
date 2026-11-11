<?php

namespace App\Http\Resources\Mobile;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * An open request that matches the handyman's skills ("Find work"). No location or homeowner details.
 *
 * @mixin ServiceRequest
 */
class OpenRequestResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => Str::limit($this->description, 160),
            'service' => $this->service->name,
            'category' => $this->service->category?->name,
            'city' => $this->service_city,
            'desired_start_date' => self::date($this->desired_start_date),
            'created_at' => self::timestamp($this->created_at),
            'offers_count' => (int) $this->offers_count,
            'has_my_offer' => (bool) $this->has_my_offer,
        ];
    }
}
