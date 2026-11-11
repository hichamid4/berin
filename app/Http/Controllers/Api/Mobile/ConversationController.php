<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\Messaging\ConversationMessaging;
use App\Actions\Messaging\ConversationProvisioner;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ConversationResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The signed-in member's inbox. Every lookup is scoped to conversations the member takes part
 * in, so another member's conversation is indistinguishable from one that does not exist (404).
 */
class ConversationController extends Controller
{
    public function index(Request $request, ConversationProvisioner $provisioner): AnonymousResourceCollection
    {
        $user = $request->user();
        $provisioner->provisionMissingFor($user);

        $conversations = self::inbox($user)
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->cursorPaginate(20);

        return ConversationResource::collection($conversations);
    }

    public function show(Request $request, int $conversation): ConversationResource
    {
        return ConversationResource::make(self::inbox($request->user())->findOrFail($conversation));
    }

    public function unreadCount(Request $request, ConversationMessaging $messaging): JsonResponse
    {
        return response()->json(['data' => $messaging->unreadSummary($request->user())]);
    }

    /**
     * @return Builder<Conversation>
     */
    public static function inbox(User $user): Builder
    {
        return Conversation::query()
            ->forParticipant($user)
            ->with(['homeowner', 'handyman.handymanProfile', 'request.service', 'offer', 'lastMessage'])
            ->withCount(['messages as unread_count' => fn (Builder $query) => $query
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')]);
    }
}
