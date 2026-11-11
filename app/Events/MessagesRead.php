<?php

namespace App\Events;

use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A participant read the messages they received in a conversation up to a given message.
 */
class MessagesRead implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $conversationId,
        public int $readerId,
        public int $senderId,
        public int $upToMessageId,
        public CarbonInterface $readAt,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.'.$this->conversationId),
            new PrivateChannel('users.'.$this->readerId),
            new PrivateChannel('users.'.$this->senderId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'reader_id' => $this->readerId,
            'up_to_message_id' => $this->upToMessageId,
            'read_at' => $this->readAt->toIso8601ZuluString('millisecond'),
        ];
    }

    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }
}
