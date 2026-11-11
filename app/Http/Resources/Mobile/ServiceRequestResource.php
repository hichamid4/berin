<?php

namespace App\Http\Resources\Mobile;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;

/**
 * A homeowner's own request. Offers are included only when they were loaded (detail view).
 *
 * @mixin ServiceRequest
 */
class ServiceRequestResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $acceptedOffer = $this->relationLoaded('acceptedOffer') ? $this->acceptedOffer : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'service' => $this->service->name,
            'category' => $this->service->relationLoaded('category') ? $this->service->category?->name : null,
            'city' => $this->service_city,
            'desired_start_date' => self::date($this->desired_start_date),
            'created_at' => self::timestamp($this->created_at),
            'offers_count' => $this->whenCounted('offers'),
            'pending_offers_count' => $this->whenCounted('pending_offers'),
            'accepted_handyman' => $acceptedOffer ? ParticipantResource::make($acceptedOffer->handyman->user) : null,
            'conversation_id' => $acceptedOffer?->relationLoaded('conversation') ? $acceptedOffer->conversation?->id : null,
            'description' => $this->when($this->relationLoaded('offers'), fn () => $this->description),
            'service_location' => $this->when($this->relationLoaded('offers'), fn () => $this->service_location),
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
        ];
    }
}
