<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function acceptedPasswords(): array
    {
        return [
            'homeowner, 8 characters' => ['homeowner', 'Abcdef1!'],
            'homeowner, 11 characters' => ['homeowner', 'Abcdefgh1!x'],
            'handyman, 12 characters' => ['handyman', 'Abcdefghi1!x'],
            'handyman, 8 characters' => ['handyman', 'Abcdef1!'],
        ];
    }

    #[DataProvider('acceptedPasswords')]
    public function test_registration_accepts_passwords_of_eight_characters_or_more(string $role, string $password): void
    {
        Notification::fake();

        $this->post(route('register.store', $role), $this->registration($role, ['password' => $password, 'password_confirmation' => $password]))
            ->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', "{$role}.flow@example.test")->firstOrFail();
        $this->assertSame($role, $user->role->value);
        $this->assertTrue(password_verify($password, $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[TestWith(['homeowner'])]
    #[TestWith(['handyman'])]
    public function test_registration_rejects_a_seven_character_password(string $role): void
    {
        $this->post(route('register.store', $role), $this->registration($role, ['password' => 'Abcde1!', 'password_confirmation' => 'Abcde1!']))
            ->assertSessionHasErrors(['password' => 'The password must be at least 8 characters.']);
    }

    #[TestWith(['homeowner'])]
    #[TestWith(['handyman'])]
    public function test_registration_requires_a_phone_number(string $role): void
    {
        $this->post(route('register.store', $role), $this->registration($role, ['phone_e164' => '']))
            ->assertSessionHasErrors(['phone_e164' => 'A phone number is required so that the other party can reach you on WhatsApp after a booking.']);
        $this->assertDatabaseMissing('users', ['email' => "{$role}.flow@example.test"]);
    }

    #[TestWith(['12345'])]
    #[TestWith(['+21261234567'])]
    #[TestWith(['+212812345678'])]
    #[TestWith(['phone-number'])]
    public function test_registration_rejects_invalid_phone_numbers(string $phone): void
    {
        $this->post(route('register.store', 'homeowner'), $this->registration('homeowner', ['phone_e164' => $phone]))
            ->assertSessionHasErrors(['phone_e164' => 'Enter a valid phone number in international format, for example +212612345678 or 0612345678.']);
    }

    #[TestWith(['06 12 34 56 78', '+212612345678'])]
    #[TestWith(['+212 7-00-11-22-33', '+212700112233'])]
    #[TestWith(['00212512345678', '+212512345678'])]
    #[TestWith(['+33612345678', '+33612345678'])]
    public function test_registration_normalizes_phone_numbers_to_e164(string $input, string $stored): void
    {
        Notification::fake();

        $this->post(route('register.store', 'handyman'), $this->registration('handyman', ['phone_e164' => $input]))
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', ['email' => 'handyman.flow@example.test', 'phone_e164' => $stored]);
    }

    public function test_verification_link_works_without_a_session_and_resend_sends_a_new_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->role(UserRole::Handyman)->create();

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);

        auth()->logout();
        $this->get($this->verificationUrl($user))
            ->assertRedirect(route('handyman.login'))
            ->assertSessionHas('status', 'email-verified-sign-in');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_expired_or_tampered_verification_links_are_rejected_with_an_explanation(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user, now()->subMinute()))
            ->assertForbidden()
            ->assertSee('This verification link is not valid');

        $tampered = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('someone-else@example.test')]);
        $this->get($tampered)->assertForbidden()->assertSee('This verification link is not valid');

        $this->get(str_replace('signature=', 'signature=x', $this->verificationUrl($user)))->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_blocked_accounts_cannot_be_verified_by_link(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $user = User::factory()->unverified()->blocked($administrator->id)->create();

        $this->get($this->verificationUrl($user))->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_email_is_branded_and_contains_the_signed_link(): void
    {
        $user = User::factory()->unverified()->create(['first_name' => 'Salma']);
        $url = $this->verificationUrl($user);

        $mail = (new VerifyEmail)->toMail($user);
        $mail->actionUrl = $url;
        $html = (string) $mail->render();

        $this->assertSame('Confirm your email address · BreIn', $mail->subject);
        $this->assertStringContainsString('Hello Salma', $html);
        $this->assertStringContainsString('Verify my email address', $html);
        $this->assertStringContainsString('/email/verify/'.$user->id.'/', $html);
    }

    #[TestWith([UserRole::Homeowner, 'login.store', 'homeowner.dashboard'])]
    #[TestWith([UserRole::Handyman, 'handyman.login.store', 'handyman.dashboard'])]
    #[TestWith([UserRole::Administrator, 'admin.login.store', 'admin.dashboard'])]
    public function test_each_role_signs_in_and_out(UserRole $role, string $loginRoute, string $dashboard): void
    {
        $user = User::factory()->role($role)->create();

        $this->post(route($loginRoute), ['email' => $user->email, 'password' => 'Test-password-2026!'])->assertRedirect(route($dashboard));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_password_reset_accepts_an_eight_character_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status', 'password-reset-link-sent');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.store'), ['token' => $token, 'email' => $user->email, 'password' => 'Short1!', 'password_confirmation' => 'Short1!'])
            ->assertSessionHasErrors('password');

        $this->post(route('password.store'), ['token' => $token, 'email' => $user->email, 'password' => 'Newpass1', 'password_confirmation' => 'Newpass1'])
            ->assertRedirect(route('login'));
        $this->assertTrue(password_verify('Newpass1', $user->fresh()->password));
    }

    public function test_account_password_change_uses_the_eight_character_minimum(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'Test-password-2026!', 'password' => 'Seven1!', 'password_confirmation' => 'Seven1!',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'Test-password-2026!', 'password' => 'Eight1!x', 'password_confirmation' => 'Eight1!x',
        ])->assertSessionHas('success');
    }

    public function test_profile_phone_is_required_for_members_and_optional_for_administrators(): void
    {
        $homeowner = User::factory()->create(['phone_e164' => '+212611111111']);
        $administrator = User::factory()->role(UserRole::Administrator)->create();

        $this->actingAs($homeowner)->put(route('account.profile.update'), $this->profile($homeowner, ['phone_e164' => '']))
            ->assertSessionHasErrors('phone_e164');
        $this->assertSame('+212611111111', $homeowner->fresh()->phone_e164);

        $this->actingAs($homeowner)->put(route('account.profile.update'), $this->profile($homeowner, ['phone_e164' => '0722334455']))
            ->assertSessionHas('success');
        $this->assertSame('+212722334455', $homeowner->fresh()->phone_e164);

        $this->actingAs($administrator)->put(route('account.profile.update'), $this->profile($administrator, ['phone_e164' => '']))
            ->assertSessionHas('success');
        $this->assertNull($administrator->fresh()->phone_e164);
    }

    public function test_mail_diagnostic_command_sends_without_printing_credentials(): void
    {
        config(['mail.default' => 'array', 'mail.mailers.array.password' => 'never-print-me']);

        $this->artisan('app:mail-test', ['to' => 'inbox@example.test'])
            ->doesntExpectOutputToContain('never-print-me')
            ->expectsOutputToContain('Test email handed to the "array" mailer for inbox@example.test.')
            ->assertSuccessful();

        $this->artisan('app:mail-test', ['to' => 'not-an-email'])->assertFailed();
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function registration(string $role, array $overrides = []): array
    {
        return [
            'first_name' => 'Flow',
            'last_name' => ucfirst($role),
            'username' => "{$role}-flow-".bin2hex(random_bytes(3)),
            'email' => "{$role}.flow@example.test",
            'phone_e164' => '+212612345678',
            'city' => 'Casablanca',
            'address' => 'Maarif',
            'password' => 'Strong-password-2026!',
            'password_confirmation' => 'Strong-password-2026!',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string|null>
     */
    private function profile(User $user, array $overrides): array
    {
        return [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'phone_e164' => $user->phone_e164,
            ...$overrides,
        ];
    }

    private function verificationUrl(User $user, $expiresAt = null): string
    {
        return URL::temporarySignedRoute('verification.verify', $expiresAt ?? now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }
}
