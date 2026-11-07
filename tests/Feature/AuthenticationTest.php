<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_homeowner_and_handyman_registration_create_role_profiles_and_send_verification(): void
    {
        Notification::fake();

        $homeownerData = $this->registrationData('homeowner');
        $this->post(route('register.store', 'homeowner'), $homeownerData)
            ->assertRedirect(route('verification.notice'));

        $homeowner = User::query()->where('email', $homeownerData['email'])->firstOrFail();
        $this->assertSame(UserRole::Homeowner, $homeowner->role);
        $this->assertNotNull($homeowner->homeownerProfile);
        $this->assertNull($homeowner->email_verified_at);
        Notification::assertSentTo($homeowner, VerifyEmail::class);

        auth()->logout();
        $this->flushSession();

        $handymanData = $this->registrationData('handyman');
        $this->post(route('register.store', 'handyman'), $handymanData)
            ->assertRedirect(route('verification.notice'));

        $handyman = User::query()->where('email', $handymanData['email'])->firstOrFail();
        $this->assertSame(UserRole::Handyman, $handyman->role);
        $this->assertNotNull($handyman->handymanProfile);
        Notification::assertSentTo($handyman, VerifyEmail::class);
    }

    public function test_email_verification_enables_the_role_dashboard(): void
    {
        Notification::fake();
        $this->post(route('register.store', 'homeowner'), $this->registrationData('homeowner'));
        $user = User::query()->where('email', 'homeowner@example.test')->firstOrFail();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($verificationUrl)->assertRedirect(route('homeowner.dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('homeowner.dashboard'))->assertOk();
    }

    public function test_homeowner_handyman_and_administrator_logins_are_role_specific(): void
    {
        $homeowner = User::factory()->create(['role' => UserRole::Homeowner]);
        $this->post(route('login.store'), ['email' => $homeowner->email, 'password' => 'Test-password-2026!'])
            ->assertRedirect(route('homeowner.dashboard'));
        $this->get(route('homeowner.dashboard'))->assertOk();
        $this->get(route('handyman.dashboard'))->assertForbidden();
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();

        $handyman = User::factory()->create(['role' => UserRole::Handyman]);
        $this->post(route('handyman.login.store'), ['email' => $handyman->email, 'password' => 'Test-password-2026!'])
            ->assertRedirect(route('handyman.dashboard'));
        $this->get(route('handyman.dashboard'))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->post(route('logout'));

        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'Test-password-2026!'])
            ->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('homeowner.dashboard'))->assertForbidden();
    }

    public function test_unverified_users_cannot_access_dashboards_and_admin_cannot_self_register(): void
    {
        $unverified = User::factory()->unverified()->create(['role' => UserRole::Homeowner]);
        $this->actingAs($unverified)->get(route('homeowner.dashboard'))->assertRedirect(route('verification.notice'));
        $this->post('/register/admin', $this->registrationData('admin'))->assertNotFound();
    }

    public function test_blocked_users_cannot_log_in_or_continue_using_a_session(): void
    {
        Notification::fake();
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $homeowner = User::factory()->create(['role' => UserRole::Homeowner]);

        $this->actingAs($homeowner)->get(route('homeowner.dashboard'))->assertOk();
        DB::table('users')->where('id', $homeowner->id)->update([
            'status' => 'blocked',
            'blocked_at' => now(),
            'blocked_by' => $administrator->id,
        ]);

        $this->get(route('homeowner.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login.store'), ['email' => $homeowner->email, 'password' => 'Test-password-2026!'])
            ->assertSessionHasErrors('email');
        $this->post(route('password.email'), ['email' => $homeowner->email])
            ->assertSessionHas('status', 'password-reset-link-sent');
        Notification::assertNothingSent();
    }

    public function test_password_reset_sends_a_token_and_updates_the_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => UserRole::Handyman]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'password-reset-link-sent');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'New-secure-password-2026!',
            'password_confirmation' => 'New-secure-password-2026!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(password_verify('New-secure-password-2026!', $user->fresh()->password));
        $this->post(route('handyman.login.store'), [
            'email' => $user->email,
            'password' => 'New-secure-password-2026!',
        ])->assertRedirect(route('handyman.dashboard'));
    }

    public function test_anonymized_accounts_cannot_authenticate_verify_or_receive_a_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => UserRole::Homeowner]);
        $originalEmail = $user->email;
        DB::table('users')->where('id', $user->id)->update([
            'status' => 'anonymized',
            'email' => null,
            'username' => null,
            'deleted_at' => now(),
            'email_verified_at' => null,
        ]);

        $this->post(route('login.store'), ['email' => $originalEmail, 'password' => 'Test-password-2026!'])
            ->assertSessionHasErrors('email');
        $this->post(route('password.email'), ['email' => $originalEmail])
            ->assertSessionHas('status', 'password-reset-link-sent');
        Notification::assertNothingSent();

        $anonymized = User::withTrashed()->findOrFail($user->id);
        $this->actingAs($anonymized)->get(route('verification.notice'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /** @return array<string, string> */
    private function registrationData(string $role): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'username' => $role.'-'.bin2hex(random_bytes(4)),
            'email' => $role.'@example.test',
            'phone_e164' => '+212612345678',
            'address' => '10 Example Street',
            'city' => 'Rabat',
            'password' => 'Strong-password-2026!',
            'password_confirmation' => 'Strong-password-2026!',
        ];
    }
}
