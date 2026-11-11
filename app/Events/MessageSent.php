<?php

namespace App\Events;

use App\Http\Resources\Mobile\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A message was stored. Dispatched only after the sending transaction commits, then broadcast
 * by the queue worker to the conversation channel and to both participants' inbox channels.
 */
class MessageSent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.'.$this->message->conversation_id),
            new PrivateChannel('users.'.$this->message->recipient_id),
            new PrivateChannel('users.'.$this->message->sender_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['message' => MessageResource::make($this->message)->resolve()];
    }

    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }
}
