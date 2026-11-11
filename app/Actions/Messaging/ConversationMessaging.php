<?php

namespace App\Actions\Messaging;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Sending and reading messages. The sender and recipient are always derived from the
 * authenticated participant and the conversation, never from client-supplied identifiers.
 */
class ConversationMessaging
{
    /**
     * Whether the user may still write in this conversation: the other participant must still
     * hold an active account (a closed or blocked member can no longer receive messages).
     */
    public function canSend(Conversation $conversation, User $sender): bool
    {
        if (! $conversation->hasParticipant($sender)) {
            return false;
        }

        $recipient = $conversation->otherParticipant($sender);

        return $recipient->status === 'active' && ! $recipient->trashed();
    }

    /**
     * Persist a text message, then broadcast it after the transaction commits.
     *
     * Retries are idempotent: the client sends the same client_message_id for every attempt of one
     * message, and (sender_id, client_message_id) is unique, so a retry returns the stored message.
     */
    public function send(Conversation $conversation, User $sender, string $body, string $clientMessageId): SentMessage
    {
        if ($existing = $this->previousAttempt($conversation, $sender, $clientMessageId)) {
            return new SentMessage($existing, created: false);
        }

        if (! $this->canSend($conversation, $sender)) {
            throw new BusinessRuleViolation('This member is no longer available, so new messages cannot be delivered.');
        }

        try {
            return DB::transaction(function () use ($conversation, $sender, $body, $clientMessageId): SentMessage {
                $locked = Conversation::query()->lockForUpdate()->findOrFail($conversation->id);

                $message = $locked->messages()->create([
                    'sender_id' => $sender->id,
                    'recipient_id' => $locked->otherParticipantId($sender),
                    'client_message_id' => $clientMessageId,
                    'body' => $body,
                ]);

                $locked->forceFill(['last_message_id' => $message->id, 'last_activity_at' => $message->created_at])->save();

                broadcast(new MessageSent($message));

                return new SentMessage($message, created: true);
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent attempt of the same message won the race; return the stored copy.
            return new SentMessage($this->previousAttempt($conversation, $sender, $clientMessageId)
                ?? throw new BusinessRuleViolation('This message could not be sent. Please try again.'), created: false);
        }
    }

    /**
     * Mark the messages the user received in this conversation as read, up to and including the
     * given message (or all of them), and tell the sender in real time.
     *
     * @return int Number of messages that changed from unread to read.
     */
    public function markRead(Conversation $conversation, User $reader, ?int $upToMessageId = null): int
    {
        $unread = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('recipient_id', $reader->id)
            ->whereNull('read_at')
            ->when($upToMessageId !== null, fn ($query) => $query->where('id', '<=', $upToMessageId));

        $lastReadId = (clone $unread)->max('id');

        if ($lastReadId === null) {
            return 0;
        }

        $readAt = now();
        $updated = $unread->where('id', '<=', $lastReadId)->update(['read_at' => $readAt]);

        if ($updated > 0) {
            broadcast(new MessagesRead($conversation->id, $reader->id, $conversation->otherParticipantId($reader), (int) $lastReadId, $readAt));
        }

        return $updated;
    }

    /**
     * Unread messages addressed to the user, overall and by number of conversations.
     *
     * @return array{messages: int, conversations: int}
     */
    public function unreadSummary(User $user): array
    {
        $summary = Message::query()
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->selectRaw('COUNT(*) AS messages, COUNT(DISTINCT conversation_id) AS conversations')
            ->toBase()
            ->first();

        return ['messages' => (int) $summary->messages, 'conversations' => (int) $summary->conversations];
    }

    private function previousAttempt(Conversation $conversation, User $sender, string $clientMessageId): ?Message
    {
        $existing = Message::query()
            ->where('sender_id', $sender->id)
            ->where('client_message_id', $clientMessageId)
            ->first();

        if ($existing !== null && $existing->conversation_id !== $conversation->id) {
            throw new BusinessRuleViolation('This message identifier was already used in another conversation.');
        }

        return $existing;
    }
}
