<?php

namespace App\Http\Resources\Mobile;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;

/**
 * A request as a handyman sees it: the exact address and the homeowner only once their offer was
 * accepted (ServiceRequestPolicy::viewPrivateLocation), and their own offers on it.
 *
 * Expects 'my_offers' to be set as a relation of the handyman's offers.
 *
 * @mixin ServiceRequest
 */
class HandymanRequestResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $canSeeLocation = $viewer->can('viewPrivateLocation', $this->resource);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'service' => $this->service->name,
            'category' => $this->service->category?->name,
            'city' => $this->service_city,
            'service_location' => $canSeeLocation ? $this->service_location : null,
            'desired_start_date' => self::date($this->desired_start_date),
            'created_at' => self::timestamp($this->created_at),
            'offers_count' => (int) $this->offers_count,
            'homeowner' => $canSeeLocation ? ParticipantResource::make($this->homeowner->user) : null,
            'photos' => PhotoResource::collection($this->photos),
            'my_offers' => OfferResource::collection($this->getRelation('myOffers')),
            'can_offer' => $viewer->can('makeOffer', $this->resource),
        ];
    }
}
