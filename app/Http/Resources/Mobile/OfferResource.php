<?php

namespace App\Http\Resources\Mobile;

use App\Models\Offer;
use Illuminate\Http\Request;

/**
 * An offer, seen by the homeowner who received it or by the handyman who made it.
 * Allowed actions come from the server-side policies, so the app never re-implements the rules.
 *
 * @mixin Offer
 */
class OfferResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isHomeownerView = $viewer->isHomeowner();
        $serviceRequest = $this->relationLoaded('request') ? $this->request : null;

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'price' => $this->formattedPrice(),
            'start_date' => self::date($this->start_date),
            'end_date' => self::date($this->end_date),
            'description' => $this->description,
            'created_at' => self::timestamp($this->created_at),
            'handyman' => $this->when($isHomeownerView && $this->relationLoaded('handyman'), fn () => ParticipantResource::make($this->handyman->user)),
            'request' => $this->when(! $isHomeownerView && $serviceRequest !== null, fn () => [
                'id' => $serviceRequest->id,
                'title' => $serviceRequest->title,
                'status' => $serviceRequest->status->value,
                'status_label' => $serviceRequest->status->label(),
                'service' => $serviceRequest->service->name,
                'city' => $serviceRequest->service_city,
            ]),
            'conversation_id' => $this->relationLoaded('conversation') ? $this->conversation?->id : null,
            'can_accept' => $isHomeownerView && $viewer->can('respond', $this->resource),
            'can_decline' => $isHomeownerView && $viewer->can('respond', $this->resource),
        ];
    }
}
