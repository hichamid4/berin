<?php

namespace App\Http\Resources\Mobile;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;

/**
 * A homeowner's own request. Offers, photos and the review are included only when loaded
 * (detail view). Allowed actions come from ServiceRequestPolicy, so the app never re-derives them.
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
        $viewer = $request->user();
        $acceptedOffer = $this->relationLoaded('acceptedOffer') ? $this->acceptedOffer : null;
        $isDetail = $this->relationLoaded('offers');
        $review = $acceptedOffer?->relationLoaded('review') ? $acceptedOffer->review : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'service_id' => $this->service_id,
            'service' => $this->service->name,
            'category' => $this->service->relationLoaded('category') ? $this->service->category?->name : null,
            'city' => $this->service_city,
            'desired_start_date' => self::date($this->desired_start_date),
            'created_at' => self::timestamp($this->created_at),
            'handyman_completed_at' => self::timestamp($this->handyman_completed_at),
            'homeowner_confirmed_at' => self::timestamp($this->homeowner_confirmed_at),
            'offers_count' => $this->whenCounted('offers'),
            'pending_offers_count' => $this->whenCounted('pending_offers'),
            'accepted_handyman' => $acceptedOffer ? ParticipantResource::make($acceptedOffer->handyman->user) : null,
            'accepted_offer_id' => $this->accepted_offer_id,
            'conversation_id' => $acceptedOffer?->relationLoaded('conversation') ? $acceptedOffer->conversation?->id : null,
            'description' => $this->when($isDetail, fn () => $this->description),
            'service_location' => $this->when($isDetail, fn () => $this->service_location),
            'photos' => PhotoResource::collection($this->whenLoaded('photos')),
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
            'review' => $this->when($isDetail, fn () => $review ? ReviewResource::make($review) : null),
            'permissions' => $this->when($isDetail, fn () => [
                'edit' => $viewer->can('update', $this->resource),
                'cancel' => $viewer->can('cancel', $this->resource),
                'confirm_completion' => $viewer->can('confirmCompletion', $this->resource),
                'review' => $viewer->can('review', $this->resource) && $review === null,
                'edit_review' => $viewer->can('review', $this->resource) && $review !== null,
            ]),
        ];
    }
}
