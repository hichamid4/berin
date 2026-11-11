<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\Messaging\ConversationMessaging;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\SendMessageRequest;
use App\Http\Resources\Mobile\MessageResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Messages of one conversation, for its participants only.
 *
 * History is loaded newest first with an opaque cursor (?cursor=...). After a WebSocket drop the
 * app catches up with ?after_id=<last id it holds>, which returns the missed messages oldest first.
 */
class MessageController extends Controller
{
    private const CATCH_UP_LIMIT = 100;

    public function index(Request $request, int $conversation): AnonymousResourceCollection
    {
        $thread = Conversation::query()->forParticipant($request->user())->findOrFail($conversation);
        $validated = $request->validate([
            'after_id' => ['sometimes', 'integer', 'min:0'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        if (array_key_exists('after_id', $validated)) {
            $missed = $thread->messages()
                ->where('id', '>', $validated['after_id'])
                ->orderBy('id')
                ->limit(self::CATCH_UP_LIMIT + 1)
                ->get();

            return MessageResource::collection($missed->take(self::CATCH_UP_LIMIT))
                ->additional(['meta' => ['has_more' => $missed->count() > self::CATCH_UP_LIMIT]]);
        }

        return MessageResource::collection(
            $thread->messages()->orderByDesc('id')->cursorPaginate($validated['limit'] ?? 30),
        );
    }

    /**
     * 201 when the message was created, 200 when this was a retry of a message already stored.
     */
    public function store(SendMessageRequest $request, int $conversation, ConversationMessaging $messaging): JsonResponse
    {
        $user = $request->user();
        $thread = Conversation::query()->forParticipant($user)->with(['homeowner', 'handyman'])->findOrFail($conversation);

        $sent = $messaging->send($thread, $user, $request->validated('body'), $request->validated('client_message_id'));

        return MessageResource::make($sent->message)->response()->setStatusCode($sent->created ? 201 : 200);
    }

    public function markRead(Request $request, int $conversation, ConversationMessaging $messaging): JsonResponse
    {
        $user = $request->user();
        $thread = Conversation::query()->forParticipant($user)->findOrFail($conversation);
        $validated = $request->validate(['up_to_message_id' => ['sometimes', 'nullable', 'integer', 'min:1']]);

        $marked = $messaging->markRead($thread, $user, $validated['up_to_message_id'] ?? null);

        return response()->json(['data' => [
            'marked' => $marked,
            'unread' => $messaging->unreadSummary($user),
        ]]);
    }
}
