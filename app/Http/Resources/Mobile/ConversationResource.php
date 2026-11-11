<?php

namespace App\Http\Resources\Mobile;

use App\Actions\Messaging\ConversationMessaging;
use App\Models\Conversation;
use Illuminate\Http\Request;

/**
 * A conversation as seen by one of its participants (the authenticated user).
 *
 * Expects homeowner, handyman.handymanProfile, request.service, offer and lastMessage loaded,
 * and an unread_count aggregate for the viewer.
 *
 * @mixin Conversation
 */
class ConversationResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'my_role' => $this->homeowner_id === $viewer->id ? 'homeowner' : 'handyman',
            'other_participant' => ParticipantResource::make($this->otherParticipant($viewer)),
            'request' => [
                'id' => $this->request->id,
                'title' => $this->request->title,
                'status' => $this->request->status->value,
                'status_label' => $this->request->status->label(),
                'service' => $this->request->service->name,
                'city' => $this->request->service_city,
            ],
            'offer' => [
                'id' => $this->offer->id,
                'price' => $this->offer->formattedPrice(),
                'start_date' => self::date($this->offer->start_date),
                'end_date' => self::date($this->offer->end_date),
            ],
            'last_message' => $this->lastMessage ? MessageResource::make($this->lastMessage) : null,
            'unread_count' => (int) ($this->unread_count ?? 0),
            'can_send' => app(ConversationMessaging::class)->canSend($this->resource, $viewer),
            'last_activity_at' => self::timestamp($this->last_activity_at),
        ];
    }
}
