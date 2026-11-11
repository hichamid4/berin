<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Skill;
use App\Models\User;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MobileMarketplaceLifecycleTest extends TestCase
{
    use DatabaseTransactions, InteractsWithMobileApi;

    private function tinyPng(string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
    }

    /**
     * @return array<string, mixed>
     */
    private function offerPayload(array $overrides = []): array
    {
        return [
            'price' => '650.00',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'description' => 'I will replace the cartridge and reseal the tap base properly.',
            ...$overrides,
        ];
    }

    // Registration ---------------------------------------------------------------------------

    public function test_homeowners_and_handymen_register_and_must_confirm_their_email_before_signing_in(): void
    {
        Notification::fake();

        foreach (['homeowner', 'handyman'] as $index => $role) {
            $email = "new.{$role}@example.test";
            $this->postJson(route('api.mobile.auth.register'), [
                'role' => $role,
                'first_name' => 'Nouha',
                'last_name' => 'Tester',
                'username' => "nouha-{$role}-{$index}",
                'email' => strtoupper($email),
                'phone_e164' => '0612345678',
                'city' => 'Rabat',
                'password' => 'Long-enough-1',
                'password_confirmation' => 'Long-enough-1',
            ])->assertCreated()->assertJsonPath('requires_email_verification', true)->assertJsonMissingPath('token');

            $user = User::query()->where('email', $email)->sole();
            $this->assertSame($role, $user->role->value);
            $this->assertNull($user->email_verified_at);
            $this->assertNotNull($role === 'homeowner' ? $user->homeownerProfile : $user->handymanProfile);
            $this->assertSame(0, $user->tokens()->count());
            Notification::assertSentTo($user, VerifyEmail::class);

            $this->postJson(route('api.mobile.auth.login'), ['email' => $email, 'password' => 'Long-enough-1', 'device_id' => 'device-0001-abcd', 'device_name' => 'Phone'])
                ->assertForbidden()->assertJsonPath('code', 'email_unverified');
        }
    }

    public function test_registration_uses_the_website_rules(): void
    {
        $existing = HomeownerProfile::factory()->create()->user;

        $this->postJson(route('api.mobile.auth.register'), [
            'role' => 'admin',
            'first_name' => 'X',
            'last_name' => 'Y',
            'username' => $existing->username,
            'email' => $existing->email,
            'phone_e164' => '',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['role', 'username', 'email', 'phone_e164', 'password']);
    }

    // Catalog, directory, favorites ---------------------------------------------------------------

    public function test_catalog_lists_only_active_services_with_market_prices(): void
    {
        $fixture = $this->openRequestWithOffer();
        $hidden = Service::factory()->create(['category_id' => $fixture['service']->category_id, 'is_active' => false]);

        $response = $this->asMobileUser($fixture['homeowner']->user)->getJson(route('api.mobile.catalog.index'))->assertOk();
        $services = collect($response->json('data'))->flatMap(fn (array $category) => $category['services']);

        $listed = $services->firstWhere('id', $fixture['service']->id);
        $this->assertNotNull($listed);
        $this->assertEquals((float) $fixture['offer']->price, $listed['price_from']);
        $this->assertNull($services->firstWhere('id', $hidden->id));

        $this->getJson(route('api.mobile.catalog.services.show', $fixture['service']))
            ->assertOk()
            ->assertJsonPath('data.handymen.0.id', $fixture['handyman']->user_id)
            ->assertJsonPath('data.open_requests', 1);
        $this->getJson(route('api.mobile.catalog.services.show', $hidden))->assertNotFound();
    }

    public function test_directory_and_favorites(): void
    {
        $fixture = $this->openRequestWithOffer();
        $homeowner = $fixture['homeowner']->user;
        $handymanId = $fixture['handyman']->user_id;

        $this->asMobileUser($homeowner);
        $this->getJson(route('api.mobile.handymen.index', ['skill' => $fixture['skill']->id]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $handymanId)
            ->assertJsonPath('data.0.is_favorite', false)
            ->assertJsonMissingPath('data.0.email');

        $this->postJson(route('api.mobile.homeowner.favorites.store', $handymanId))->assertOk()->assertJsonPath('data.is_favorite', true);
        $this->postJson(route('api.mobile.homeowner.favorites.store', $handymanId))->assertOk();
        $this->getJson(route('api.mobile.homeowner.favorites.index'))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $handymanId);
        $this->getJson(route('api.mobile.handymen.show', $handymanId))->assertOk()->assertJsonPath('data.is_favorite', true)->assertJsonStructure(['data' => ['rating_breakdown', 'services', 'reviews']]);

        $this->deleteJson(route('api.mobile.homeowner.favorites.destroy', $handymanId))->assertOk()->assertJsonPath('data.is_favorite', false);
        $this->getJson(route('api.mobile.homeowner.favorites.index'))->assertOk()->assertJsonCount(0, 'data');

        $this->asMobileUser($fixture['handyman']->user)->getJson(route('api.mobile.homeowner.favorites.index'))->assertForbidden();
    }

    // Homeowner requests ---------------------------------------------------------------------------

    public function test_homeowner_creates_edits_and_cancels_a_request_with_private_photos(): void
    {
        Storage::fake('local');
        $fixture = $this->openRequestWithOffer();
        $homeowner = $fixture['homeowner']->user;
        $this->asMobileUser($homeowner);

        $created = $this->post(route('api.mobile.homeowner.requests.store'), [
            'service_id' => $fixture['service']->id,
            'title' => 'Repaint the living room walls',
            'description' => 'Two walls need a fresh coat of white paint after the plaster repair.',
            'service_location' => '24 Rue des Fleurs, Gauthier',
            'service_city' => 'Casablanca',
            'desired_start_date' => now()->addWeek()->toDateString(),
            'photos' => [$this->tinyPng()],
        ])->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.permissions.edit', true)
            ->assertJsonCount(1, 'data.photos');

        $id = $created->json('data.id');
        $this->get($created->json('data.photos.0.url'))->assertOk();

        $this->post(route('api.mobile.homeowner.requests.update', $id), [
            'title' => 'Repaint the living room and hallway',
            'description' => 'Two walls and the hallway need a fresh coat of white paint after repairs.',
            'service_location' => '24 Rue des Fleurs, Gauthier',
            'service_city' => 'Casablanca',
            'desired_start_date' => now()->addWeek()->toDateString(),
        ])->assertOk()->assertJsonPath('data.title', 'Repaint the living room and hallway');

        $this->postJson(route('api.mobile.homeowner.requests.cancel', $id))->assertOk()->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.permissions.edit', false);

        $intruder = HomeownerProfile::factory()->create()->user;
        $this->asMobileUser($intruder)->postJson(route('api.mobile.homeowner.requests.cancel', $fixture['request']))->assertForbidden();
        $this->get($created->json('data.photos.0.url'))->assertForbidden();
    }

    public function test_request_validation_matches_the_website(): void
    {
        $fixture = $this->openRequestWithOffer();

        $this->asMobileUser($fixture['homeowner']->user)->postJson(route('api.mobile.homeowner.requests.store'), [
            'service_id' => 999999,
            'title' => 'Short',
            'description' => 'Too short',
            'service_location' => '',
            'service_city' => '',
            'desired_start_date' => now()->subDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['service_id', 'title', 'description', 'service_location', 'service_city', 'desired_start_date']);
    }

    // Full job lifecycle ---------------------------------------------------------------------------

    public function test_full_job_lifecycle_from_offer_to_review(): void
    {
        $fixture = $this->openRequestWithOffer();
        $fixture['offer']->delete();
        $homeowner = $fixture['homeowner']->user;
        $handyman = $fixture['handyman']->user;
        $requestId = $fixture['request']->id;

        // The handyman finds the request; the address and homeowner stay private before booking.
        $this->asMobileUser($handyman);
        $this->getJson(route('api.mobile.handyman.requests.show', $requestId))
            ->assertOk()
            ->assertJsonPath('data.can_offer', true)
            ->assertJsonPath('data.service_location', null)
            ->assertJsonPath('data.homeowner', null);

        $offerId = $this->postJson(route('api.mobile.handyman.offers.store', $requestId), $this->offerPayload())
            ->assertCreated()
            ->assertJsonPath('data.can_offer', false)
            ->assertJsonPath('data.my_offers.0.status', 'pending')
            ->assertJsonPath('data.my_offers.0.can_edit', true)
            ->json('data.my_offers.0.id');

        $this->postJson(route('api.mobile.handyman.offers.store', $requestId), $this->offerPayload())
            ->assertUnprocessable()->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'already sent an offer'));

        $this->postJson(route('api.mobile.handyman.offers.update', $offerId), $this->offerPayload(['price' => '600']))
            ->assertOk()->assertJsonPath('data.my_offers.0.price', '600 MAD');

        // The homeowner compares and accepts; the conversation opens.
        $this->asMobileUser($homeowner);
        $this->getJson(route('api.mobile.homeowner.requests.show', $requestId))
            ->assertJsonPath('data.offers.0.id', $offerId)
            ->assertJsonPath('data.offers.0.can_accept', true);
        $accepted = $this->postJson(route('api.mobile.homeowner.offers.accept', $offerId))->assertOk()->assertJsonPath('data.status', 'booked');
        $this->assertSame(Conversation::query()->where('offer_id', $offerId)->value('id'), $accepted->json('data.conversation_id'));

        // After booking the handyman sees the address and homeowner, can no longer edit or withdraw.
        $this->asMobileUser($handyman);
        $this->getJson(route('api.mobile.handyman.requests.show', $requestId))
            ->assertJsonPath('data.service_location', $fixture['request']->service_location)
            ->assertJsonPath('data.homeowner.id', $homeowner->id)
            ->assertJsonPath('data.my_offers.0.can_edit', false)
            ->assertJsonPath('data.my_offers.0.can_withdraw', false)
            ->assertJsonPath('data.my_offers.0.can_mark_completed', true);

        $this->postJson(route('api.mobile.handyman.offers.complete', $offerId))->assertOk()->assertJsonPath('data.status', 'completion_pending');

        // The homeowner confirms and reviews.
        $this->asMobileUser($homeowner);
        $this->getJson(route('api.mobile.homeowner.requests.show', $requestId))->assertJsonPath('data.permissions.confirm_completion', true);
        $this->postJson(route('api.mobile.homeowner.requests.confirm', $requestId))->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.permissions.review', true);

        $this->postJson(route('api.mobile.homeowner.requests.review.store', $requestId), ['rating' => 5, 'comment' => 'Quick and tidy work.'])
            ->assertCreated()
            ->assertJsonPath('data.review.rating', 5)
            ->assertJsonPath('data.permissions.review', false)
            ->assertJsonPath('data.permissions.edit_review', true);
        $this->putJson(route('api.mobile.homeowner.requests.review.update', $requestId), ['rating' => 4, 'comment' => null])
            ->assertOk()->assertJsonPath('data.review.rating', 4);

        $this->assertSame(4, Review::query()->findOrFail($offerId)->rating);
        $this->assertSame(RequestStatus::Completed, ServiceRequest::query()->findOrFail($requestId)->status);

        // The conversation stays available after completion.
        $this->getJson(route('api.mobile.conversations.index'))->assertJsonPath('data.0.request.status', 'completed');
    }

    public function test_handyman_withdraws_a_pending_offer_and_cannot_complete_a_job_that_is_not_theirs(): void
    {
        $fixture = $this->openRequestWithOffer();
        $this->asMobileUser($fixture['handyman']->user);

        $this->postJson(route('api.mobile.handyman.offers.withdraw', $fixture['offer']))->assertOk()
            ->assertJsonPath('data.my_offers.0.status', OfferStatus::Withdrawn->value)
            ->assertJsonPath('data.can_offer', true);
        $this->postJson(route('api.mobile.handyman.offers.complete', $fixture['offer']))->assertForbidden();

        $other = HandymanProfile::factory()->create();
        $this->asMobileUser($other->user)->getJson(route('api.mobile.handyman.requests.show', $fixture['request']))->assertForbidden();
    }

    // Profile, password, skills ---------------------------------------------------------------------

    public function test_profile_password_and_skills(): void
    {
        $handyman = HandymanProfile::factory()->create();
        $user = $handyman->user;
        $otherDevice = $this->mobileToken($user);
        $thisDevice = $this->mobileToken($user);
        $this->asMobileUser($user, $thisDevice);

        $this->post(route('api.mobile.me.update'), [
            'first_name' => 'Karim',
            'last_name' => 'Updated',
            'username' => $user->username,
            'email' => $user->email,
            'phone_e164' => '+212600000099',
            'city' => 'Tanger',
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.name', 'Karim Updated')
            ->assertJsonPath('data.city', 'Tanger')
            ->assertJsonPath('email_verification_required', false);

        $this->putJson(route('api.mobile.me.password'), ['current_password' => 'wrong', 'password' => 'Brand-new-pass-1', 'password_confirmation' => 'Brand-new-pass-1'])
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->putJson(route('api.mobile.me.password'), ['current_password' => 'Test-password-2026!', 'password' => 'Brand-new-pass-1', 'password_confirmation' => 'Brand-new-pass-1'])
            ->assertOk();
        $this->assertNull(PersonalAccessToken::findToken($otherDevice));
        $this->assertNotNull(PersonalAccessToken::findToken($thisDevice));

        $skills = Skill::factory()->count(2)->create();
        $this->putJson(route('api.mobile.handyman.skills.update'), ['skill_ids' => $skills->pluck('id')->all()])
            ->assertOk();
        $this->assertEqualsCanonicalizing($skills->pluck('id')->all(), $handyman->skills()->pluck('skills.id')->all());
        $this->putJson(route('api.mobile.handyman.skills.update'), ['skill_ids' => []])->assertUnprocessable();
        $this->getJson(route('api.mobile.me'))->assertJsonCount(2, 'data.skills');
    }

    public function test_changing_the_email_requires_confirming_it_again(): void
    {
        Notification::fake();
        $user = HomeownerProfile::factory()->create()->user;
        $this->asMobileUser($user);

        $this->post(route('api.mobile.me.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => 'brand.new.address@example.test',
            'phone_e164' => '+212600000098',
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('email_verification_required', true);

        Notification::assertSentTo($user->refresh(), VerifyEmail::class);
        $this->getJson(route('api.mobile.me'))->assertForbidden()->assertJsonPath('code', 'email_unverified');
    }

    public function test_category_model_is_reused_for_catalog_icons(): void
    {
        $category = Category::factory()->create();
        Service::factory()->create(['category_id' => $category->id]);

        $this->asMobileUser(HomeownerProfile::factory()->create()->user)->getJson(route('api.mobile.catalog.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => $category->id, 'icon' => $category->icon()]);
    }
}
