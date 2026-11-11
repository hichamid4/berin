<?php

namespace App\Http\Resources\Mobile;

use App\Models\Message;
use Illuminate\Http\Request;

/**
 * @mixin Message
 */
class MessageResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'recipient_id' => $this->recipient_id,
            'client_message_id' => $this->client_message_id,
            'body' => $this->body,
            'created_at' => self::timestamp($this->created_at),
            'read_at' => self::timestamp($this->read_at),
        ];
    }
}
