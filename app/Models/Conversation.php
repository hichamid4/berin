<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Private mobile conversation between the homeowner and the handyman of one accepted offer.
 */
#[Fillable(['offer_id', 'request_id', 'homeowner_id', 'handyman_id', 'last_activity_at'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function homeowner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeowner_id')->withTrashed();
    }

    public function handyman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handyman_id')->withTrashed();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function hasParticipant(User $user): bool
    {
        return $this->homeowner_id === $user->id || $this->handyman_id === $user->id;
    }

    /**
     * The participant who is not the given user. Callers must check hasParticipant() first.
     */
    public function otherParticipant(User $user): User
    {
        return $this->homeowner_id === $user->id ? $this->handyman : $this->homeowner;
    }

    public function otherParticipantId(User $user): int
    {
        return $this->homeowner_id === $user->id ? $this->handyman_id : $this->homeowner_id;
    }

    /**
     * Restrict to conversations in which the user is one of the two participants.
     */
    #[Scope]
    protected function forParticipant(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $inner) => $inner
            ->where('conversations.homeowner_id', $user->id)
            ->orWhere('conversations.handyman_id', $user->id));
    }
}
