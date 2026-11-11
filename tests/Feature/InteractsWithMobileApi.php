<?php

namespace Tests\Feature;

use App\Actions\Marketplace\MarketplaceWorkflow;
use App\Actions\Messaging\ConversationProvisioner;
use App\Models\Conversation;
use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\Offer;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Helpers for the mobile API tests: real device tokens and marketplace fixtures built through
 * the same workflow the website and the app use. Test classes combine this with DatabaseTransactions.
 */
trait InteractsWithMobileApi
{
    protected function mobileToken(User $user, array $abilities = ['mobile']): string
    {
        return $user->createToken('mobile|phpunit-'.Str::lower(Str::random(10)).'|PHPUnit device', $abilities)->plainTextToken;
    }

    /**
     * Send the following requests as this member's phone. Guards are reset so switching between
     * members inside one test never reuses the previously authenticated user.
     */
    protected function asMobileUser(User $user, ?string $token = null): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token ?? $this->mobileToken($user))->withHeader('Accept', 'application/json');
    }

    /**
     * An open request whose service needs a skill the handyman holds, with one pending offer.
     *
     * @return array{homeowner: HomeownerProfile, handyman: HandymanProfile, service: Service, skill: Skill, request: ServiceRequest, offer: Offer}
     */
    protected function openRequestWithOffer(): array
    {
        $skill = Skill::factory()->create();
        $service = Service::factory()->create();
        $service->skills()->attach($skill);
        $homeowner = HomeownerProfile::factory()->create();
        $handyman = HandymanProfile::factory()->create();
        $handyman->skills()->attach($skill);

        $request = ServiceRequest::factory()->create(['homeowner_id' => $homeowner->user_id, 'service_id' => $service->id]);
        $offer = Offer::factory()->create(['request_id' => $request->id, 'handyman_id' => $handyman->user_id]);

        return compact('homeowner', 'handyman', 'service', 'skill', 'request', 'offer');
    }

    /**
     * A booked job accepted through the marketplace workflow, with its conversation.
     *
     * @return array{homeowner: User, handyman: User, request: ServiceRequest, offer: Offer, conversation: Conversation}
     */
    protected function bookedConversation(?User $homeowner = null): array
    {
        $fixture = $this->openRequestWithOffer();

        if ($homeowner !== null) {
            $fixture['request']->update(['homeowner_id' => $homeowner->id]);
            $fixture['offer']->setRelation('request', $fixture['request']);
        }

        $homeowner ??= $fixture['homeowner']->user;
        app(MarketplaceWorkflow::class)->acceptOffer($fixture['offer'], $homeowner);
        $offer = $fixture['offer']->refresh();
        $request = $fixture['request']->refresh();

        return [
            'homeowner' => $homeowner,
            'handyman' => $fixture['handyman']->user,
            'request' => $request,
            'offer' => $offer,
            'conversation' => app(ConversationProvisioner::class)->openForAcceptedOffer($offer, $request),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messagePayload(string $body = 'Hello, I can come on Tuesday morning.', ?string $clientMessageId = null): array
    {
        return ['body' => $body, 'client_message_id' => $clientMessageId ?? (string) Str::uuid()];
    }
}
