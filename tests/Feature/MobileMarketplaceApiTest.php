<?php

namespace Tests\Feature;

use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\Offer;
use App\Models\ServiceRequest;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MobileMarketplaceApiTest extends TestCase
{
    use DatabaseTransactions, InteractsWithMobileApi;

    public function test_homeowner_sees_only_their_own_requests_and_offers_with_server_side_permissions(): void
    {
        $fixture = $this->openRequestWithOffer();
        $otherRequest = ServiceRequest::factory()->create(['homeowner_id' => HomeownerProfile::factory()->create()->user_id]);

        $this->asMobileUser($fixture['homeowner']->user);
        $this->getJson(route('api.mobile.homeowner.requests.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $fixture['request']->id)
            ->assertJsonPath('data.0.pending_offers_count', 1);

        $this->getJson(route('api.mobile.homeowner.requests.show', $fixture['request']->id))
            ->assertOk()
            ->assertJsonPath('data.offers.0.id', $fixture['offer']->id)
            ->assertJsonPath('data.offers.0.can_accept', true)
            ->assertJsonPath('data.offers.0.handyman.id', $fixture['handyman']->user_id)
            ->assertJsonMissingPath('data.offers.0.handyman.email');

        $this->getJson(route('api.mobile.homeowner.requests.show', $otherRequest->id))->assertNotFound();
    }

    public function test_another_homeowner_cannot_accept_an_offer(): void
    {
        $fixture = $this->openRequestWithOffer();

        $this->asMobileUser(HomeownerProfile::factory()->create()->user)
            ->postJson(route('api.mobile.homeowner.offers.accept', $fixture['offer']))
            ->assertForbidden();

        $this->assertSame(OfferStatus::Pending, $fixture['offer']->refresh()->status);
    }

    public function test_an_offer_that_is_no_longer_pending_cannot_be_accepted(): void
    {
        $fixture = $this->openRequestWithOffer();
        $fixture['offer']->update(['status' => OfferStatus::Declined]);

        $this->asMobileUser($fixture['homeowner']->user)
            ->postJson(route('api.mobile.homeowner.offers.accept', $fixture['offer']))
            ->assertForbidden();

        $this->assertSame(RequestStatus::Open, $fixture['request']->refresh()->status);
    }

    public function test_homeowner_declines_an_offer(): void
    {
        $fixture = $this->openRequestWithOffer();

        $this->asMobileUser($fixture['homeowner']->user)
            ->postJson(route('api.mobile.homeowner.offers.decline', $fixture['offer']))
            ->assertOk()
            ->assertJsonPath('data.status', RequestStatus::Open->value);

        $this->assertSame(OfferStatus::Declined, $fixture['offer']->refresh()->status);
    }

    public function test_roles_are_enforced_on_role_specific_endpoints(): void
    {
        $fixture = $this->openRequestWithOffer();

        $this->asMobileUser($fixture['handyman']->user);
        $this->getJson(route('api.mobile.homeowner.requests.index'))->assertForbidden();
        $this->postJson(route('api.mobile.homeowner.offers.accept', $fixture['offer']))->assertForbidden();

        $this->asMobileUser($fixture['homeowner']->user);
        $this->getJson(route('api.mobile.handyman.offers.index'))->assertForbidden();
        $this->getJson(route('api.mobile.handyman.requests.index'))->assertForbidden();
    }

    public function test_handyman_sees_own_offers_and_skill_matched_open_requests(): void
    {
        $fixture = $this->openRequestWithOffer();
        $unmatched = ServiceRequest::factory()->create();
        Offer::factory()->create(['handyman_id' => HandymanProfile::factory()->create()->user_id, 'request_id' => $fixture['request']->id]);

        $this->asMobileUser($fixture['handyman']->user);
        $this->getJson(route('api.mobile.handyman.offers.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $fixture['offer']->id)
            ->assertJsonPath('data.0.request.title', $fixture['request']->title);

        $work = $this->getJson(route('api.mobile.handyman.requests.index'))->assertOk();
        $ids = array_column($work->json('data'), 'id');
        $this->assertContains($fixture['request']->id, $ids);
        $this->assertNotContains($unmatched->id, $ids);
        $this->assertTrue(collect($work->json('data'))->firstWhere('id', $fixture['request']->id)['has_my_offer']);
    }

    public function test_home_summary_is_role_aware(): void
    {
        $fixture = $this->openRequestWithOffer();

        $this->asMobileUser($fixture['homeowner']->user)->getJson(route('api.mobile.home'))
            ->assertOk()
            ->assertJsonPath('data.role', 'homeowner')
            ->assertJsonPath('data.stats.open_requests', 1)
            ->assertJsonPath('data.stats.offers_to_review', 1);

        $this->asMobileUser($fixture['handyman']->user)->getJson(route('api.mobile.home'))
            ->assertOk()
            ->assertJsonPath('data.role', 'handyman')
            ->assertJsonPath('data.stats.pending_offers', 1);
    }
}
