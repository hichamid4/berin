<?php

namespace Tests\Feature;

use App\Actions\Accounts\AccountLifecycle;
use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MobileAuthenticationTest extends TestCase
{
    use DatabaseTransactions, InteractsWithMobileApi;

    private const PASSWORD = 'Test-password-2026!';

    private function login(User $user, string $deviceId = 'device-0001-abcd', string $password = self::PASSWORD): TestResponse
    {
        return $this->postJson(route('api.mobile.auth.login'), [
            'email' => strtoupper($user->email),
            'password' => $password,
            'device_id' => $deviceId,
            'device_name' => 'Pixel 8 (Android 15)',
        ]);
    }

    public function test_homeowner_signs_in_and_the_role_comes_from_the_account(): void
    {
        $homeowner = HomeownerProfile::factory()->create()->user;

        $response = $this->login($homeowner)->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $homeowner->id)
            ->assertJsonPath('user.role', 'homeowner')
            ->assertJsonPath('user.email', $homeowner->email);

        $token = PersonalAccessToken::findToken($response->json('token'));
        $this->assertTrue($token->tokenable->is($homeowner));
        $this->assertSame(['mobile'], $token->abilities);
        $this->assertNotNull($token->expires_at);
        $this->assertStringStartsWith('mobile|device-0001-abcd|', $token->name);
    }

    public function test_handyman_signs_in_with_the_same_endpoint(): void
    {
        $handyman = HandymanProfile::factory()->create()->user;

        $this->login($handyman)->assertOk()->assertJsonPath('user.role', 'handyman');
    }

    public function test_signing_in_again_on_a_device_replaces_only_that_devices_token(): void
    {
        $homeowner = HomeownerProfile::factory()->create()->user;

        $first = $this->login($homeowner, 'device-aaaa-1111')->json('token');
        $this->login($homeowner, 'device-bbbb-2222')->assertOk();
        $second = $this->login($homeowner, 'device-aaaa-1111')->json('token');

        $this->assertSame(2, $homeowner->tokens()->count());
        $this->assertNull(PersonalAccessToken::findToken($first));
        $this->assertNotNull(PersonalAccessToken::findToken($second));
    }

    public function test_wrong_password_is_rejected_without_issuing_a_token(): void
    {
        $homeowner = HomeownerProfile::factory()->create()->user;

        $this->login($homeowner, password: 'not-the-password')->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson(route('api.mobile.auth.login'), ['email' => 'nobody@example.test', 'password' => 'x', 'device_id' => 'device-0001-abcd', 'device_name' => 'Phone'])
            ->assertUnprocessable();

        $this->assertSame(0, $homeowner->tokens()->count());
    }

    public function test_login_requires_a_device_identifier(): void
    {
        $homeowner = HomeownerProfile::factory()->create()->user;

        $this->postJson(route('api.mobile.auth.login'), ['email' => $homeowner->email, 'password' => self::PASSWORD, 'device_id' => 'bad id%', 'device_name' => 'Phone'])
            ->assertUnprocessable()->assertJsonValidationErrors(['device_id']);
    }

    public function test_administrators_are_refused_because_administration_is_web_only(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();

        $this->login($administrator)->assertForbidden()->assertJsonPath('code', 'role_not_supported');
        $this->assertSame(0, $administrator->tokens()->count());
    }

    public function test_blocked_accounts_are_refused(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $handyman = HandymanProfile::factory()->create()->user;
        $handyman->forceFill(['status' => 'blocked', 'blocked_at' => now(), 'blocked_by' => $administrator->id])->save();

        $this->login($handyman)->assertForbidden()->assertJsonPath('code', 'account_unavailable');
        $this->assertSame(0, $handyman->tokens()->count());
    }

    public function test_closed_accounts_cannot_sign_in(): void
    {
        $homeowner = HomeownerProfile::factory()->create()->user;
        $email = $homeowner->email;
        app(AccountLifecycle::class)->anonymize($homeowner, $homeowner);

        $this->postJson(route('api.mobile.auth.login'), ['email' => $email, 'password' => self::PASSWORD, 'device_id' => 'device-0001-abcd', 'device_name' => 'Phone'])
            ->assertUnprocessable();
    }

    public function test_unverified_accounts_must_confirm_their_email_first_and_get_a_fresh_link(): void
    {
        Notification::fake();
        $homeowner = User::factory()->unverified()->create();
        HomeownerProfile::factory()->create(['user_id' => $homeowner->id]);

        $this->login($homeowner)->assertForbidden()->assertJsonPath('code', 'email_unverified');
        $this->login($homeowner)->assertForbidden();

        $this->assertSame(0, $homeowner->tokens()->count());
        Notification::assertSentToTimes($homeowner, VerifyEmail::class, 1);
    }

    public function test_profile_requires_a_token_and_returns_only_the_signed_in_member(): void
    {
        $handyman = HandymanProfile::factory()->create()->user;

        $this->getJson(route('api.mobile.me'))->assertUnauthorized();
        $this->asMobileUser($handyman)->getJson(route('api.mobile.me'))
            ->assertOk()
            ->assertJsonPath('data.id', $handyman->id)
            ->assertJsonPath('data.role', 'handyman')
            ->assertJsonPath('data.email', $handyman->email);
    }

    public function test_logout_revokes_the_current_device_token_only(): void
    {
        $homeowner = HomeownerProfile::factory()->create()->user;
        $phone = $this->mobileToken($homeowner);
        $tablet = $this->mobileToken($homeowner);

        $this->asMobileUser($homeowner, $phone)->postJson(route('api.mobile.auth.logout'))->assertNoContent();

        $this->assertNull(PersonalAccessToken::findToken($phone));
        $this->assertNotNull(PersonalAccessToken::findToken($tablet));
        $this->asMobileUser($homeowner, $phone)->getJson(route('api.mobile.me'))->assertUnauthorized();
        $this->asMobileUser($homeowner, $tablet)->getJson(route('api.mobile.me'))->assertOk();
    }

    public function test_an_account_blocked_by_an_administrator_loses_app_access_at_the_next_request(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $handyman = HandymanProfile::factory()->create()->user;
        $token = $this->mobileToken($handyman);

        app(AccountLifecycle::class)->block($handyman, $administrator);

        $this->asMobileUser($handyman, $token)->getJson(route('api.mobile.me'))->assertForbidden()->assertJsonPath('code', 'account_unavailable');
        $this->assertSame(0, $handyman->tokens()->count());
        $this->asMobileUser($handyman, $token)->getJson(route('api.mobile.me'))->assertUnauthorized();
    }

    public function test_a_token_of_an_account_blocked_outside_the_workflow_stops_working_and_is_revoked(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $handyman = HandymanProfile::factory()->create()->user;
        $token = $this->mobileToken($handyman);
        $handyman->forceFill(['status' => 'blocked', 'blocked_at' => now(), 'blocked_by' => $administrator->id])->save();

        $this->asMobileUser($handyman, $token)->getJson(route('api.mobile.me'))->assertForbidden()->assertJsonPath('code', 'account_unavailable');
        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    public function test_tokens_without_the_mobile_ability_and_administrator_tokens_are_refused(): void
    {
        $homeowner = HomeownerProfile::factory()->create()->user;
        $administrator = User::factory()->role(UserRole::Administrator)->create();

        $this->asMobileUser($homeowner, $this->mobileToken($homeowner, ['other']))->getJson(route('api.mobile.me'))->assertUnauthorized();
        $this->asMobileUser($administrator)->getJson(route('api.mobile.me'))->assertForbidden()->assertJsonPath('code', 'role_not_supported');
    }

    public function test_a_website_session_does_not_authenticate_the_mobile_api(): void
    {
        $homeowner = HomeownerProfile::factory()->create()->user;

        $this->actingAs($homeowner)->getJson(route('api.mobile.me'))->assertUnauthorized();
    }

    public function test_sign_in_attempts_are_throttled(): void
    {
        $homeowner = HomeownerProfile::factory()->create()->user;

        foreach (range(1, 5) as $attempt) {
            $this->login($homeowner, password: 'wrong-password-'.$attempt)->assertUnprocessable();
        }

        $this->login($homeowner)->assertTooManyRequests();
    }
}
