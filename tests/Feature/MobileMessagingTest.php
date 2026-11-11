<?php

namespace Tests\Feature;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Actions\Messaging\ConversationProvisioner;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Conversation;
use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\Message;
use App\Models\Offer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\OfferStatus;
use App\RequestStatus;
use App\UserRole;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileMessagingTest extends TestCase
{
    use DatabaseTransactions, InteractsWithMobileApi;

    // Conversation creation ------------------------------------------------------------------

    public function test_accepting_an_offer_in_the_app_opens_exactly_one_conversation_for_both_parties(): void
    {
        $fixture = $this->openRequestWithOffer();
        $competing = Offer::factory()->create(['request_id' => $fixture['request']->id, 'handyman_id' => HandymanProfile::factory()->create()->user_id]);

        $response = $this->asMobileUser($fixture['homeowner']->user)
            ->postJson(route('api.mobile.homeowner.offers.accept', $fixture['offer']))
            ->assertOk()
            ->assertJsonPath('data.status', RequestStatus::Booked->value);

        $conversation = Conversation::query()->where('offer_id', $fixture['offer']->id)->sole();
        $response->assertJsonPath('data.conversation_id', $conversation->id);
        $this->assertSame($fixture['homeowner']->user_id, $conversation->homeowner_id);
        $this->assertSame($fixture['handyman']->user_id, $conversation->handyman_id);
        $this->assertSame($fixture['request']->id, $conversation->request_id);
        $this->assertSame(OfferStatus::Superseded, $competing->refresh()->status);
        $this->assertFalse(Conversation::query()->where('offer_id', $competing->id)->exists());

        $this->asMobileUser($fixture['handyman']->user)->getJson(route('api.mobile.conversations.index'))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $conversation->id);
    }

    public function test_website_acceptance_is_unchanged_and_the_app_then_provides_the_conversation(): void
    {
        $fixture = $this->openRequestWithOffer();

        $this->actingAs($fixture['homeowner']->user)
            ->post(route('homeowner.offers.accept', $fixture['offer']))
            ->assertRedirect(route('homeowner.requests.show', $fixture['request']))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'WhatsApp'));

        $this->assertFalse(Conversation::query()->where('offer_id', $fixture['offer']->id)->exists());

        $this->asMobileUser($fixture['handyman']->user)->getJson(route('api.mobile.handyman.offers.index'))
            ->assertOk()
            ->assertJsonPath('data.0.status', OfferStatus::Accepted->value)
            ->assertJsonPath('data.0.conversation_id', Conversation::query()->where('offer_id', $fixture['offer']->id)->value('id'));
        $this->asMobileUser($fixture['homeowner']->user)->getJson(route('api.mobile.conversations.index'))->assertOk()->assertJsonCount(1, 'data');

        $this->assertSame(1, Conversation::query()->where('offer_id', $fixture['offer']->id)->count());
    }

    public function test_accepted_offers_from_before_messaging_get_one_conversation_without_duplicates(): void
    {
        $fixture = $this->openRequestWithOffer();
        $fixture['offer']->update(['status' => OfferStatus::Accepted, 'accepted_at' => now()]);
        ServiceRequest::query()->whereKey($fixture['request']->id)->update(['status' => RequestStatus::Booked, 'accepted_offer_id' => $fixture['offer']->id]);
        $this->assertFalse(Conversation::query()->where('offer_id', $fixture['offer']->id)->exists());

        $this->asMobileUser($fixture['handyman']->user)->getJson(route('api.mobile.conversations.index'))->assertOk()->assertJsonCount(1, 'data');
        $this->asMobileUser($fixture['homeowner']->user)->getJson(route('api.mobile.conversations.index'))->assertOk()->assertJsonCount(1, 'data');

        $this->assertSame(1, Conversation::query()->where('offer_id', $fixture['offer']->id)->count());
        $this->assertSame(0, app(ConversationProvisioner::class)->provisionMissingFor($fixture['homeowner']->user));
    }

    public function test_pending_declined_withdrawn_and_superseded_offers_never_get_a_conversation(): void
    {
        $fixture = $this->openRequestWithOffer();
        $statuses = [OfferStatus::Declined, OfferStatus::Withdrawn, OfferStatus::Superseded];

        foreach ($statuses as $status) {
            Offer::factory()->create(['request_id' => $fixture['request']->id, 'handyman_id' => HandymanProfile::factory()->create()->user_id, 'status' => $status]);
        }

        app(ConversationProvisioner::class)->provisionMissingFor($fixture['homeowner']->user);
        $this->assertFalse(Conversation::query()->where('request_id', $fixture['request']->id)->exists());

        $this->expectException(BusinessRuleViolation::class);
        app(ConversationProvisioner::class)->openForAcceptedOffer($fixture['offer'], $fixture['request']);
    }

    public function test_reopening_the_conversation_of_an_accepted_offer_returns_the_same_row(): void
    {
        $booked = $this->bookedConversation();

        $again = app(ConversationProvisioner::class)->openForAcceptedOffer($booked['offer'], $booked['request']);

        $this->assertTrue($again->is($booked['conversation']));
        $this->assertSame(1, Conversation::query()->where('offer_id', $booked['offer']->id)->count());
    }

    // Access control ---------------------------------------------------------------------------

    public function test_both_participants_can_open_the_conversation_and_see_the_other_party_only(): void
    {
        $booked = $this->bookedConversation();

        $this->asMobileUser($booked['homeowner'])->getJson(route('api.mobile.conversations.show', $booked['conversation']->id))
            ->assertOk()
            ->assertJsonPath('data.my_role', 'homeowner')
            ->assertJsonPath('data.other_participant.id', $booked['handyman']->id)
            ->assertJsonPath('data.other_participant.name', $booked['handyman']->fullName())
            ->assertJsonPath('data.request.title', $booked['request']->title)
            ->assertJsonPath('data.can_send', true)
            ->assertJsonMissingPath('data.other_participant.email')
            ->assertJsonMissingPath('data.other_participant.phone');

        $this->asMobileUser($booked['handyman'])->getJson(route('api.mobile.conversations.show', $booked['conversation']->id))
            ->assertOk()
            ->assertJsonPath('data.my_role', 'handyman')
            ->assertJsonPath('data.other_participant.id', $booked['homeowner']->id);
    }

    public function test_unrelated_members_cannot_see_or_use_a_conversation(): void
    {
        $booked = $this->bookedConversation();
        $id = $booked['conversation']->id;
        Message::factory()->create(['conversation_id' => $id]);

        foreach ([HomeownerProfile::factory()->create()->user, HandymanProfile::factory()->create()->user] as $intruder) {
            $this->asMobileUser($intruder);
            $this->getJson(route('api.mobile.conversations.index'))->assertOk()->assertJsonCount(0, 'data');
            $this->getJson(route('api.mobile.conversations.show', $id))->assertNotFound();
            $this->getJson(route('api.mobile.messages.index', $id))->assertNotFound();
            $this->postJson(route('api.mobile.messages.store', $id), $this->messagePayload())->assertNotFound();
            $this->postJson(route('api.mobile.messages.read', $id))->assertNotFound();
        }

        $this->assertSame(1, Message::query()->where('conversation_id', $id)->count());
        $this->assertNull(Message::query()->where('conversation_id', $id)->value('read_at'));
    }

    public function test_handymen_who_were_not_accepted_have_no_conversation(): void
    {
        $fixture = $this->openRequestWithOffer();
        $loser = HandymanProfile::factory()->create();
        $losingOffer = Offer::factory()->create(['request_id' => $fixture['request']->id, 'handyman_id' => $loser->user_id]);
        app(MarketplaceWorkflow::class)->acceptOffer($fixture['offer'], $fixture['homeowner']->user);
        app(ConversationProvisioner::class)->provisionMissingFor($fixture['homeowner']->user);

        $conversation = Conversation::query()->where('offer_id', $fixture['offer']->id)->sole();
        $this->assertSame(OfferStatus::Superseded, $losingOffer->refresh()->status);
        $this->assertFalse(Conversation::query()->where('offer_id', $losingOffer->id)->exists());

        $this->asMobileUser($loser->user);
        $this->getJson(route('api.mobile.conversations.index'))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(route('api.mobile.conversations.show', $conversation->id))->assertNotFound();
    }

    // Sending ------------------------------------------------------------------------------------

    public function test_a_message_is_stored_with_the_sender_from_the_token_and_the_derived_recipient(): void
    {
        $booked = $this->bookedConversation();
        $outsider = User::factory()->role(UserRole::Homeowner)->create();
        $payload = [...$this->messagePayload('  Bonjour, je passe mardi à 9h.  '), 'sender_id' => $outsider->id, 'recipient_id' => $outsider->id];

        $response = $this->asMobileUser($booked['handyman'])
            ->postJson(route('api.mobile.messages.store', $booked['conversation']->id), $payload)
            ->assertCreated()
            ->assertJsonPath('data.sender_id', $booked['handyman']->id)
            ->assertJsonPath('data.recipient_id', $booked['homeowner']->id)
            ->assertJsonPath('data.body', 'Bonjour, je passe mardi à 9h.')
            ->assertJsonPath('data.read_at', null);

        $message = Message::query()->findOrFail($response->json('data.id'));
        $this->assertSame($booked['handyman']->id, $message->sender_id);
        $this->assertSame($booked['homeowner']->id, $message->recipient_id);
        $this->assertNotNull($message->created_at);

        $conversation = $booked['conversation']->refresh();
        $this->assertSame($message->id, $conversation->last_message_id);
        $this->assertEquals($message->created_at, $conversation->last_activity_at);
    }

    public function test_retrying_a_message_with_the_same_client_id_never_duplicates_it(): void
    {
        $booked = $this->bookedConversation();
        $payload = $this->messagePayload('Are you available tomorrow?');
        $url = route('api.mobile.messages.store', $booked['conversation']->id);

        $first = $this->asMobileUser($booked['homeowner'])->postJson($url, $payload)->assertCreated();
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.id', $first->json('data.id'));
        $this->postJson($url, [...$payload, 'client_message_id' => strtoupper($payload['client_message_id'])])->assertOk()->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertSame(1, Message::query()->where('conversation_id', $booked['conversation']->id)->count());
    }

    public function test_a_client_message_id_cannot_be_replayed_into_another_conversation(): void
    {
        $first = $this->bookedConversation();
        $payload = $this->messagePayload();
        $this->asMobileUser($first['homeowner'])->postJson(route('api.mobile.messages.store', $first['conversation']->id), $payload)->assertCreated();

        $otherConversation = $this->bookedConversation($first['homeowner'])['conversation'];

        $this->asMobileUser($first['homeowner'])->postJson(route('api.mobile.messages.store', $otherConversation->id), $payload)->assertUnprocessable();
        $this->assertSame(0, $otherConversation->messages()->count());
    }

    public function test_message_validation(): void
    {
        $booked = $this->bookedConversation();
        $url = route('api.mobile.messages.store', $booked['conversation']->id);
        $this->asMobileUser($booked['homeowner']);

        $this->postJson($url, $this->messagePayload('   '))->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->postJson($url, $this->messagePayload(str_repeat('a', Message::MAX_BODY_LENGTH + 1)))->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->postJson($url, ['body' => 'Hello'])->assertUnprocessable()->assertJsonValidationErrors('client_message_id');
        $this->postJson($url, ['body' => 'Hello', 'client_message_id' => 'not-a-uuid'])->assertUnprocessable()->assertJsonValidationErrors('client_message_id');
        $this->postJson($url, $this->messagePayload(str_repeat('a', Message::MAX_BODY_LENGTH)))->assertCreated();
    }

    public function test_no_new_messages_once_the_other_member_is_no_longer_available(): void
    {
        $booked = $this->bookedConversation();
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $booked['handyman']->forceFill(['status' => 'blocked', 'blocked_at' => now(), 'blocked_by' => $administrator->id])->save();

        $this->asMobileUser($booked['homeowner']);
        $this->getJson(route('api.mobile.conversations.show', $booked['conversation']->id))->assertOk()->assertJsonPath('data.can_send', false)
            ->assertJsonPath('data.other_participant.is_available', false);
        $this->postJson(route('api.mobile.messages.store', $booked['conversation']->id), $this->messagePayload())->assertUnprocessable();
        $this->assertSame(0, $booked['conversation']->messages()->count());
    }

    public function test_the_broadcast_is_queued_after_the_message_is_committed(): void
    {
        Queue::fake();
        $booked = $this->bookedConversation();

        $id = $this->asMobileUser($booked['handyman'])
            ->postJson(route('api.mobile.messages.store', $booked['conversation']->id), $this->messagePayload())
            ->assertCreated()->json('data.id');

        Queue::assertPushedOn('broadcasts', BroadcastEvent::class, function (BroadcastEvent $job) use ($id, $booked): bool {
            $channels = collect($job->event->broadcastOn())->map(fn ($channel): string => $channel->name)->all();

            return $job->event instanceof MessageSent
                && $job->event->message->id === $id
                && $job->event->broadcastAs() === 'message.sent'
                && $channels === ['private-conversations.'.$booked['conversation']->id, 'private-users.'.$booked['homeowner']->id, 'private-users.'.$booked['handyman']->id]
                && $job->event->broadcastWith()['message']['body'] === 'Hello, I can come on Tuesday morning.';
        });
    }

    public function test_sending_is_rate_limited(): void
    {
        $booked = $this->bookedConversation();
        $url = route('api.mobile.messages.store', $booked['conversation']->id);
        $this->asMobileUser($booked['homeowner']);

        foreach (range(1, 30) as $attempt) {
            $this->postJson($url, $this->messagePayload('Message '.$attempt))->assertCreated();
        }

        $this->postJson($url, $this->messagePayload('One too many'))->assertTooManyRequests();
    }

    // History, read status and unread counts ----------------------------------------------------

    public function test_history_is_paginated_newest_first_with_a_cursor_and_can_catch_up_after_a_disconnect(): void
    {
        $booked = $this->bookedConversation();
        $ids = collect(range(1, 7))->map(fn (int $n): int => Message::factory()->create([
            'conversation_id' => $booked['conversation']->id,
            'body' => 'Message '.$n,
        ])->id);
        $url = route('api.mobile.messages.index', $booked['conversation']->id);
        $this->asMobileUser($booked['homeowner']);

        $page = $this->getJson($url.'?limit=3')->assertOk()->assertJsonCount(3, 'data');
        $this->assertSame($ids->reverse()->take(3)->values()->all(), array_column($page->json('data'), 'id'));

        $cursor = $page->json('meta.next_cursor');
        $this->assertNotNull($cursor);
        $older = $this->getJson($url.'?limit=3&cursor='.$cursor)->assertOk();
        $this->assertSame($ids->reverse()->slice(3, 3)->values()->all(), array_column($older->json('data'), 'id'));

        $missed = $this->getJson($url.'?after_id='.$ids[4])->assertOk()->assertJsonPath('meta.has_more', false);
        $this->assertSame([$ids[5], $ids[6]], array_column($missed->json('data'), 'id'));
    }

    public function test_reading_marks_only_messages_received_and_updates_unread_counts(): void
    {
        Queue::fake();
        $booked = $this->bookedConversation();
        $conversationId = $booked['conversation']->id;
        $toHomeowner = Message::factory()->count(3)->create(['conversation_id' => $conversationId]);
        $toHandyman = Message::factory()->create(['conversation_id' => $conversationId, 'sender_id' => $booked['homeowner']->id, 'recipient_id' => $booked['handyman']->id]);

        $this->asMobileUser($booked['homeowner']);
        $this->getJson(route('api.mobile.conversations.unread'))->assertOk()->assertJsonPath('data', ['messages' => 3, 'conversations' => 1]);
        $this->getJson(route('api.mobile.conversations.index'))->assertJsonPath('data.0.unread_count', 3)
            ->assertJsonPath('data.0.last_message', null);

        $this->postJson(route('api.mobile.messages.read', $conversationId), ['up_to_message_id' => $toHomeowner[1]->id])
            ->assertOk()
            ->assertJsonPath('data.marked', 2)
            ->assertJsonPath('data.unread.messages', 1);

        $this->postJson(route('api.mobile.messages.read', $conversationId))->assertOk()->assertJsonPath('data.marked', 1);
        $this->postJson(route('api.mobile.messages.read', $conversationId))->assertOk()->assertJsonPath('data.marked', 0);

        $this->assertSame(0, Message::query()->whereIn('id', $toHomeowner->pluck('id'))->whereNull('read_at')->count());
        $this->assertNull($toHandyman->refresh()->read_at);

        $this->asMobileUser($booked['handyman'])->getJson(route('api.mobile.conversations.unread'))->assertJsonPath('data', ['messages' => 1, 'conversations' => 1]);

        Queue::assertPushedOn('broadcasts', BroadcastEvent::class, fn (BroadcastEvent $job): bool => $job->event instanceof MessagesRead
            && $job->event->readerId === $booked['homeowner']->id
            && $job->event->upToMessageId === $toHomeowner[1]->id);
        Queue::assertPushed(BroadcastEvent::class, fn (BroadcastEvent $job): bool => $job->event instanceof MessagesRead && $job->event->upToMessageId === $toHomeowner[2]->id);
    }

    public function test_the_inbox_lists_the_last_message_and_newest_activity_first(): void
    {
        $older = $this->bookedConversation();
        $homeowner = $older['homeowner'];
        $this->travel(1)->minutes();
        $newer = $this->bookedConversation($homeowner);

        $this->asMobileUser($homeowner);
        $this->travel(1)->minutes();
        $this->postJson(route('api.mobile.messages.store', $older['conversation']->id), $this->messagePayload('Latest news'))->assertCreated();

        $this->getJson(route('api.mobile.conversations.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $older['conversation']->id)
            ->assertJsonPath('data.0.last_message.body', 'Latest news')
            ->assertJsonPath('data.0.unread_count', 0)
            ->assertJsonPath('data.1.id', $newer['conversation']->id);
    }

    public function test_message_identifiers_in_the_payload_are_normalised(): void
    {
        $booked = $this->bookedConversation();
        $uuid = (string) Str::uuid();

        $this->asMobileUser($booked['homeowner'])
            ->postJson(route('api.mobile.messages.store', $booked['conversation']->id), $this->messagePayload("Line one\r\nLine two", strtoupper($uuid)))
            ->assertCreated()
            ->assertJsonPath('data.client_message_id', $uuid)
            ->assertJsonPath('data.body', "Line one\nLine two");
    }
}
