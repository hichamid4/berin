<?php

namespace App\Http\Resources\Mobile;

use App\Models\Offer;
use Illuminate\Http\Request;

/**
 * An offer, seen by the homeowner who received it or by the handyman who made it.
 * Allowed actions come from OfferPolicy, so the app never re-implements the marketplace rules.
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
        $canRespond = $isHomeownerView && $serviceRequest !== null && $viewer->can('respond', $this->resource);

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'price' => $this->formattedPrice(),
            'price_amount' => (float) $this->price,
            'currency' => $this->currency,
            'start_date' => self::date($this->start_date),
            'end_date' => self::date($this->end_date),
            'description' => $this->description,
            'created_at' => self::timestamp($this->created_at),
            'handyman' => $this->when($isHomeownerView && $this->relationLoaded('handyman'), fn () => ParticipantResource::make($this->handyman->user)),
            'handyman_rating' => $this->when($isHomeownerView && isset($this->handyman_rating), fn () => $this->handyman_rating),
            'request' => $this->when(! $isHomeownerView && $serviceRequest !== null, fn () => [
                'id' => $serviceRequest->id,
                'title' => $serviceRequest->title,
                'status' => $serviceRequest->status->value,
                'status_label' => $serviceRequest->status->label(),
                'service' => $serviceRequest->service->name,
                'city' => $serviceRequest->service_city,
            ]),
            'photos' => PhotoResource::collection($this->whenLoaded('photos')),
            'conversation_id' => $this->relationLoaded('conversation') ? $this->conversation?->id : null,
            'can_accept' => $canRespond,
            'can_decline' => $canRespond,
            'can_edit' => ! $isHomeownerView && $serviceRequest !== null && $viewer->can('update', $this->resource),
            'can_withdraw' => ! $isHomeownerView && $viewer->can('withdraw', $this->resource),
            'can_mark_completed' => ! $isHomeownerView && $serviceRequest !== null && $viewer->can('markCompleted', $this->resource),
        ];
    }
}
