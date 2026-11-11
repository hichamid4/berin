<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| Private channels of the mobile chat. The authorization endpoint runs behind auth:sanctum and
| the mobile account checks, so only active, verified homeowners and handymen reach these callbacks.
*/

// A conversation's messages and read receipts: its two participants only.
Broadcast::channel('conversations.{conversationId}', function (User $user, int $conversationId): bool {
    return Conversation::query()->forParticipant($user)->whereKey($conversationId)->exists();
});

// A member's own inbox updates (new messages and read receipts across all their conversations).
Broadcast::channel('users.{userId}', function (User $user, int $userId): bool {
    return $user->id === $userId;
});
