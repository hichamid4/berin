<?php

namespace Tests\Feature;

use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\Offer;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Skill;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private HomeownerProfile $homeowner;

    private HandymanProfile $handyman;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $skill = Skill::factory()->create();
        $this->service = Service::factory()->create();
        $this->service->skills()->attach($skill);
        $this->homeowner = HomeownerProfile::factory()->create();
        $this->handyman = HandymanProfile::factory()->create();
        $this->handyman->skills()->attach($skill);
        $this->handyman->user->update(['phone_e164' => '+212600000001']);
    }

    public function test_homeowner_posts_a_request_with_a_private_photo(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->homeowner->user)->post(route('homeowner.requests.store'), [
            'service_id' => $this->service->id,
            'title' => 'Fix the leaking kitchen tap',
            'description' => 'The kitchen tap drips constantly and the cabinet is damp.',
            'service_location' => '12 Rue Test, Maarif',
            'service_city' => 'Casablanca',
            'desired_start_date' => now()->addDays(3)->toDateString(),
            'photos' => [UploadedFile::fake()->createWithContent(
                'tap.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='),
            )],
        ]);

        $request = ServiceRequest::query()->where('homeowner_id', $this->homeowner->user_id)->firstOrFail();
        $response->assertRedirect(route('homeowner.requests.show', $request));
        $this->assertSame(RequestStatus::Open, $request->status);
        $this->assertCount(1, $request->photos);
        Storage::disk('local')->assertExists($request->photos->first()->storage_path);

        $this->actingAs($this->homeowner->user)->get(route('homeowner.requests.show', $request))->assertOk()->assertSee('Fix the leaking kitchen tap');
        $this->actingAs($this->homeowner->user)->get(route('photos.request', $request->photos->first()))->assertOk();
    }

    public function test_request_validation_rejects_past_dates(): void
    {
        $this->actingAs($this->homeowner->user)->post(route('homeowner.requests.store'), [
            'service_id' => $this->service->id,
            'title' => 'Fix the leaking kitchen tap',
            'description' => 'The kitchen tap drips constantly and the cabinet is damp.',
            'service_location' => '12 Rue Test',
            'service_city' => 'Casablanca',
            'desired_start_date' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('desired_start_date');
    }

    public function test_only_skill_matched_handymen_see_a_request_and_the_address_stays_private(): void
    {
        $request = $this->openRequest();
        $unmatched = HandymanProfile::factory()->create();

        $this->actingAs($this->handyman->user)->get(route('handyman.requests.index'))->assertOk()->assertSee($request->title);
        $this->actingAs($this->handyman->user)->get(route('handyman.requests.show', $request))
            ->assertOk()
            ->assertDontSee($request->service_location)
            ->assertSee('Shared only once your offer is accepted');

        $this->actingAs($unmatched->user)->get(route('handyman.requests.index'))->assertOk()->assertDontSee($request->title);
        $this->actingAs($unmatched->user)->get(route('handyman.requests.show', $request))->assertForbidden();
    }

    public function test_handyman_submits_one_active_offer_per_request(): void
    {
        $request = $this->openRequest();
        $payload = [
            'price' => '850.50',
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'description' => 'Replace the cartridge and seals, test for leaks, parts included.',
        ];

        $this->actingAs($this->handyman->user)->post(route('handyman.offers.store', $request), $payload)
            ->assertRedirect(route('handyman.requests.show', $request));
        $this->assertDatabaseHas('offers', ['request_id' => $request->id, 'handyman_id' => $this->handyman->user_id, 'price' => '850.50', 'status' => 'pending']);

        $this->actingAs($this->handyman->user)->post(route('handyman.offers.store', $request), $payload)
            ->assertSessionHas('error', 'You already sent an offer for this request. You can edit or withdraw it below; a second offer was not created.');
        $this->assertSame(1, $request->offers()->count());
    }

    public function test_submitted_offer_is_shown_to_the_handyman_and_the_owning_homeowner(): void
    {
        $request = $this->openRequest();

        $this->actingAs($this->handyman->user)->post(route('handyman.offers.store', $request), [
            'price' => '640',
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'description' => 'Full deep cleaning with eco-friendly products, two people, one day.',
        ])->assertRedirect(route('handyman.requests.show', $request))->assertSessionHas('success');

        $offer = $request->offers()->sole();
        $this->assertSame($this->handyman->user_id, $offer->handyman_id);
        $this->assertSame(OfferStatus::Pending, $offer->status);
        $this->assertSame('640.00', $offer->price);

        $this->actingAs($this->handyman->user)->get(route('handyman.requests.show', $request))
            ->assertOk()->assertSee('Your offer')->assertSee('Pending')->assertSeeInOrder(['Offers so far', '1']);
        $this->actingAs($this->handyman->user)->get(route('handyman.requests.index'))
            ->assertOk()->assertSee('You sent an offer')->assertSee('1 offer');

        $this->actingAs($this->homeowner->user)->get(route('homeowner.requests.show', $request))
            ->assertOk()
            ->assertSee($this->handyman->user->fullName())
            ->assertSee('640')
            ->assertSee('Accept offer')
            ->assertSee('Decline');
        $this->actingAs($this->homeowner->user)->get(route('homeowner.dashboard'))->assertOk()->assertSee($this->handyman->user->fullName());
    }

    public function test_rejected_offer_returns_to_the_form_with_a_visible_summary_and_saves_nothing(): void
    {
        $request = $this->openRequest();

        $this->actingAs($this->handyman->user)
            ->from(route('handyman.requests.show', $request))
            ->post(route('handyman.offers.store', $request), [
                'price' => '500',
                'start_date' => now()->addDays(4)->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(),
                'description' => 'I can do it',
            ])
            ->assertRedirect(route('handyman.requests.show', $request).'#offer')
            ->assertSessionHasErrors(['description' => 'The description field must be at least 20 characters.']);

        $this->assertSame(0, $request->offers()->count());

        $this->actingAs($this->handyman->user)
            ->from(route('handyman.requests.show', $request))
            ->followingRedirects()
            ->post(route('handyman.offers.store', $request), ['price' => '', 'description' => 'Too short'])
            ->assertOk()
            ->assertSee('Your offer was not sent. Please correct the following:')
            ->assertSee('The price field is required.');
        $this->assertSame(0, $request->offers()->count());
    }

    public function test_offer_end_date_cannot_precede_start_date(): void
    {
        $this->actingAs($this->handyman->user)->post(route('handyman.offers.store', $this->openRequest()), [
            'price' => '300',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'description' => 'Replace the cartridge and seals, test for leaks, parts included.',
        ])->assertSessionHasErrors(['end_date' => 'The end date cannot be before the start date.']);
    }

    public function test_accepting_an_offer_books_the_request_and_supersedes_other_offers(): void
    {
        $request = $this->openRequest();
        $chosen = $this->offerFrom($this->handyman, $request);
        $other = $this->offerFrom($this->otherMatchingHandyman(), $request);

        $this->actingAs($this->homeowner->user)->post(route('homeowner.offers.accept', $chosen))
            ->assertRedirect(route('homeowner.requests.show', $request))
            ->assertSessionHas('success');

        $request->refresh();
        $this->assertSame(RequestStatus::Booked, $request->status);
        $this->assertSame($chosen->id, $request->accepted_offer_id);
        $this->assertSame(OfferStatus::Accepted, $chosen->fresh()->status);
        $this->assertSame(OfferStatus::Superseded, $other->fresh()->status);

        $this->actingAs($this->homeowner->user)->get(route('homeowner.requests.show', $request))->assertSee('https://wa.me/212600000001', false);
        $this->actingAs($this->handyman->user)->get(route('handyman.requests.show', $request))->assertSee($request->service_location);
        $this->actingAs($this->homeowner->user)->post(route('homeowner.offers.accept', $other))->assertForbidden();
    }

    public function test_another_homeowner_cannot_accept_or_view_the_request(): void
    {
        $request = $this->openRequest();
        $offer = $this->offerFrom($this->handyman, $request);
        $intruder = HomeownerProfile::factory()->create();

        $this->actingAs($intruder->user)->post(route('homeowner.offers.accept', $offer))->assertForbidden();
        $this->actingAs($intruder->user)->get(route('homeowner.requests.show', $request))->assertForbidden();
        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
    }

    public function test_completion_confirmation_and_single_review(): void
    {
        $request = $this->openRequest();
        $offer = $this->offerFrom($this->handyman, $request);
        $this->actingAs($this->homeowner->user)->post(route('homeowner.offers.accept', $offer));

        $this->actingAs($this->homeowner->user)->post(route('homeowner.requests.review.store', $request), ['rating' => 5])->assertForbidden();

        $this->actingAs($this->handyman->user)->post(route('handyman.offers.complete', $offer))->assertSessionHas('success');
        $this->assertSame(RequestStatus::CompletionPending, $request->fresh()->status);

        $this->actingAs($this->homeowner->user)->post(route('homeowner.requests.confirm', $request))->assertSessionHas('success');
        $this->assertSame(RequestStatus::Completed, $request->fresh()->status);

        $this->actingAs($this->homeowner->user)->post(route('homeowner.requests.review.store', $request), ['rating' => 4, 'comment' => 'Clean and fast work.'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', ['offer_id' => $offer->id, 'rating' => 4]);

        $this->actingAs($this->homeowner->user)->post(route('homeowner.requests.review.store', $request), ['rating' => 1])
            ->assertSessionHas('error', 'This job has already been reviewed. You can edit your existing review instead.');

        $this->actingAs($this->homeowner->user)->put(route('homeowner.requests.review.update', $request), ['rating' => 5, 'comment' => null])->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', ['offer_id' => $offer->id, 'rating' => 5, 'comment' => null]);
    }

    public function test_cancelling_an_open_request_declines_pending_offers_and_booked_requests_cannot_be_cancelled(): void
    {
        $request = $this->openRequest();
        $offer = $this->offerFrom($this->handyman, $request);

        $this->actingAs($this->homeowner->user)->post(route('homeowner.requests.cancel', $request))->assertSessionHas('success');
        $this->assertSame(RequestStatus::Cancelled, $request->fresh()->status);
        $this->assertSame(OfferStatus::Declined, $offer->fresh()->status);

        $booked = $this->openRequest();
        $this->actingAs($this->homeowner->user)->post(route('homeowner.offers.accept', $this->offerFrom($this->handyman, $booked)));
        $this->actingAs($this->homeowner->user)->post(route('homeowner.requests.cancel', $booked))->assertForbidden();
        $this->assertSame(RequestStatus::Booked, $booked->fresh()->status);
    }

    public function test_handyman_edits_and_withdraws_a_pending_offer(): void
    {
        $offer = $this->offerFrom($this->handyman, $this->openRequest());

        $this->actingAs($this->handyman->user)->put(route('handyman.offers.update', $offer), [
            'price' => '999',
            'start_date' => now()->addDays(6)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'description' => 'Updated scope: cartridge, seals and a new flexible hose.',
        ])->assertSessionHas('success');
        $this->assertSame('999.00', $offer->fresh()->price);

        $this->actingAs($this->handyman->user)->post(route('handyman.offers.withdraw', $offer))->assertSessionHas('success');
        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);
    }

    public function test_homeowner_adds_and_removes_a_favorite_handyman(): void
    {
        $this->actingAs($this->homeowner->user)->get(route('handymen.show', $this->handyman))->assertOk()->assertSee('Add to favorites');

        $this->actingAs($this->homeowner->user)->post(route('homeowner.favorites.store', $this->handyman))->assertSessionHas('success');
        $this->actingAs($this->homeowner->user)->get(route('handymen.show', $this->handyman))->assertOk()->assertSee('Remove from favorites');
        $this->assertDatabaseHas('handyman_favorites', ['homeowner_id' => $this->homeowner->user_id, 'handyman_id' => $this->handyman->user_id]);

        $this->actingAs($this->homeowner->user)->delete(route('homeowner.favorites.destroy', $this->handyman))->assertSessionHas('success');
        $this->assertDatabaseMissing('handyman_favorites', ['homeowner_id' => $this->homeowner->user_id, 'handyman_id' => $this->handyman->user_id]);

        $this->actingAs($this->handyman->user)->post(route('homeowner.favorites.store', $this->handyman))->assertForbidden();
    }

    public function test_homeowner_edits_an_open_request_but_not_a_booked_one(): void
    {
        $request = $this->openRequest();
        $payload = [
            'title' => 'Updated: leaking tap and cabinet',
            'description' => 'The leak got worse; the cabinet floor is now soaked.',
            'service_location' => 'Updated private address',
            'service_city' => 'Rabat',
            'desired_start_date' => now()->addDays(2)->toDateString(),
        ];

        $this->actingAs($this->homeowner->user)->put(route('homeowner.requests.update', $request), $payload)->assertSessionHas('success');
        $this->assertSame('Rabat', $request->fresh()->service_city);

        $this->actingAs($this->homeowner->user)->post(route('homeowner.offers.accept', $this->offerFrom($this->handyman, $request)));
        $this->actingAs($this->homeowner->user)->put(route('homeowner.requests.update', $request), [...$payload, 'service_city' => 'Fes'])->assertForbidden();
        $this->assertSame('Rabat', $request->fresh()->service_city);
    }

    public function test_homeowner_declines_an_offer_and_whatsapp_stays_hidden_before_acceptance(): void
    {
        $request = $this->openRequest();
        $offer = $this->offerFrom($this->handyman, $request);

        $this->actingAs($this->homeowner->user)->get(route('homeowner.requests.show', $request))->assertOk()->assertDontSee('wa.me', false);

        $this->actingAs($this->homeowner->user)->post(route('homeowner.offers.decline', $offer))->assertSessionHas('success');
        $this->assertSame(OfferStatus::Declined, $offer->fresh()->status);
        $this->assertSame(RequestStatus::Open, $request->fresh()->status);
    }

    public function test_offer_photos_are_stored_and_only_participants_can_open_request_and_offer_photos(): void
    {
        Storage::fake('local');
        $request = $this->openRequest();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        $this->actingAs($this->homeowner->user)->put(route('homeowner.requests.update', $request), [
            'title' => $request->title,
            'description' => $request->description,
            'service_location' => $request->service_location,
            'service_city' => $request->service_city,
            'desired_start_date' => now()->addDays(3)->toDateString(),
            'photos' => [UploadedFile::fake()->createWithContent('room.png', $png)],
        ])->assertSessionHas('success');

        $this->actingAs($this->handyman->user)->post(route('handyman.offers.store', $request), [
            'price' => '500',
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'description' => 'Full repair with a photo of a similar finished job.',
            'photos' => [UploadedFile::fake()->createWithContent('previous-job.png', $png)],
        ])->assertSessionHas('success');

        $requestPhoto = $request->photos()->firstOrFail();
        $offerPhoto = $request->offers()->firstOrFail()->photos()->firstOrFail();
        $outsider = HomeownerProfile::factory()->create();
        $unmatched = HandymanProfile::factory()->create();

        $this->actingAs($this->homeowner->user)->get(route('photos.offer', $offerPhoto))->assertOk();
        $this->actingAs($this->handyman->user)->get(route('photos.request', $requestPhoto))->assertOk();
        $this->actingAs($outsider->user)->get(route('photos.request', $requestPhoto))->assertForbidden();
        $this->actingAs($outsider->user)->get(route('photos.offer', $offerPhoto))->assertForbidden();
        $this->actingAs($unmatched->user)->get(route('photos.request', $requestPhoto))->assertForbidden();
        auth()->logout();
        $this->get(route('photos.request', $requestPhoto))->assertRedirect(route('login'));

        $this->actingAs($this->homeowner->user)->delete(route('homeowner.requests.photos.destroy', [$request, $requestPhoto]))->assertSessionHas('success');
        $this->assertModelMissing($requestPhoto);
        Storage::disk('local')->assertMissing($requestPhoto->storage_path);
    }

    private function openRequest(): ServiceRequest
    {
        return ServiceRequest::factory()->create([
            'homeowner_id' => $this->homeowner->user_id,
            'service_id' => $this->service->id,
            'service_location' => 'Private address '.fake()->unique()->numberBetween(1000, 9999),
        ]);
    }

    private function offerFrom(HandymanProfile $handyman, ServiceRequest $request): Offer
    {
        return Offer::factory()->create(['request_id' => $request->id, 'handyman_id' => $handyman->user_id]);
    }

    private function otherMatchingHandyman(): HandymanProfile
    {
        $handyman = HandymanProfile::factory()->create();
        $handyman->skills()->attach($this->service->skills->pluck('id'));

        return $handyman;
    }
}
