<?php

namespace Tests\Feature;

use App\Actions\Accounts\AdministratorPath;
use App\Actions\Accounts\AdministratorSession;
use App\Models\ActivityEvent;
use App\Models\TwoFactorCredential;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdministratorAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'Test-password-2026!';

    /**
     * Routes reachable before the second factor: the sign-in steps themselves.
     */
    private const SIGN_IN_ROUTES = ['admin.login', 'admin.login.store', 'admin.two-factor.challenge', 'admin.two-factor.verify', 'admin.two-factor.setup', 'admin.two-factor.confirm'];

    private function enrolledAdministrator(array $recoveryCodes = []): User
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        TwoFactorCredential::factory()->for($administrator)->withRecoveryCodes($recoveryCodes)->create();

        return $administrator;
    }

    private function signInWithPassword(User $administrator): TestResponse
    {
        return $this->post(route('admin.login.store'), ['email' => $administrator->email, 'password' => self::PASSWORD]);
    }

    /**
     * Every protected administration route with placeholder parameters.
     *
     * @return list<array{0: string, 1: string}> [method, uri]
     */
    private function protectedAdministrationRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            /** @var RoutingRoute $route */
            $name = (string) $route->getName();

            if (! str_starts_with($name, 'admin.') || in_array($name, self::SIGN_IN_ROUTES, true)) {
                continue;
            }

            $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
            $routes[] = [collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD')->first(), $uri];
        }

        $this->assertGreaterThan(40, count($routes), 'The administration route list looks incomplete.');

        return $routes;
    }

    // ----- Private path and public pages -------------------------------------------------------

    public function test_the_administration_area_is_served_only_under_the_private_path(): void
    {
        $prefix = AdministratorPath::prefix();

        $this->assertSame('phpunit-test-only-administration-area', $prefix);
        $this->assertStringStartsWith(url($prefix).'/', route('admin.login'));
        $this->assertStringStartsWith(url($prefix).'/', route('admin.dashboard'));

        foreach (['/admin', '/admin/login', '/admin/dashboard', '/admin/users', '/admin/contact-messages', '/administrator/login'] as $legacyPath) {
            $this->get($legacyPath)->assertNotFound();
        }
        $this->post('/admin/login', ['email' => 'x@example.test', 'password' => 'x'])->assertNotFound();
    }

    public function test_public_pages_never_link_to_or_reveal_the_administration_area(): void
    {
        $prefix = AdministratorPath::prefix();

        foreach ([route('home'), route('services.index'), route('handymen.index'), route('about'), route('contact'), route('login'), route('handyman.login'), route('register', 'homeowner'), route('password.request'), '/robots.txt'] as $url) {
            $content = (string) $this->get($url)->getContent();

            $this->assertStringNotContainsString($prefix, $content, "{$url} reveals the private administration path.");
            $this->assertStringNotContainsString('Administrator sign in', $content, "{$url} links to the administrator sign-in.");
            $this->assertStringNotContainsString('>Administration<', $content, "{$url} links to the administration area.");
            $this->assertDoesNotMatchRegularExpression('#href="[^"]*/admin(/|")#', $content, "{$url} links to /admin.");
        }
    }

    public function test_the_private_path_is_not_derived_from_a_predictable_default(): void
    {
        config(['admin.path' => null]);
        $fallback = AdministratorPath::prefix();
        $this->assertFalse(AdministratorPath::isConfigured());
        $this->assertNotContains($fallback, ['admin', 'administrator', 'administration']);
        $this->assertGreaterThanOrEqual(AdministratorPath::MIN_LENGTH, strlen($fallback));

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->assertNotSame($fallback, AdministratorPath::prefix(), 'The fallback must depend on the secret application key.');

        foreach (['admin', 'short-path', 'has spaces in the path value', 'two/segments-that-are-long-enough', str_repeat('a', 23)] as $weak) {
            $this->assertFalse(AdministratorPath::isAcceptable($weak), "'{$weak}' should be rejected.");
        }
        $this->assertTrue(AdministratorPath::isAcceptable(AdministratorPath::generate()));
        $this->assertNotSame(AdministratorPath::generate(), AdministratorPath::generate());
    }

    public function test_administration_pages_are_never_indexed_cached_or_leaked_as_a_referrer(): void
    {
        $response = $this->get(route('admin.login'))->assertOk();

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->assertHeader('Referrer-Policy', 'same-origin');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $response->assertSee('<meta name="robots" content="noindex, nofollow, noarchive">', false);
        $response->assertDontSee('name="remember"', false);
    }

    // ----- Direct access to every administration route -----------------------------------------

    public function test_guests_are_sent_to_the_sign_in_page_from_every_administration_route(): void
    {
        foreach ($this->protectedAdministrationRoutes() as [$method, $uri]) {
            $response = $this->call($method, $uri);

            $this->assertTrue($response->isRedirect(route('admin.login')), "Guest {$method} {$uri} returned {$response->getStatusCode()}.");
        }

        $this->assertGuest();
    }

    /**
     * @return array<string, array{0: UserRole}>
     */
    public static function nonAdministratorRoles(): array
    {
        return ['homeowner' => [UserRole::Homeowner], 'handyman' => [UserRole::Handyman]];
    }

    #[DataProvider('nonAdministratorRoles')]
    public function test_homeowners_and_handymen_are_refused_on_every_administration_route(UserRole $role): void
    {
        $member = User::factory()->role($role)->create();

        foreach ($this->protectedAdministrationRoutes() as [$method, $uri]) {
            $status = $this->actingAs($member)->call($method, $uri)->getStatusCode();

            $this->assertSame(403, $status, "{$role->value} {$method} {$uri} returned {$status}.");
        }
    }

    public function test_an_administrator_session_without_the_second_factor_is_ended_on_every_route(): void
    {
        $administrator = $this->enrolledAdministrator();

        foreach ([...$this->protectedAdministrationRoutes(), ['GET', '/account']] as [$method, $uri]) {
            $response = $this->actingAs($administrator)->call($method, $uri);

            $this->assertTrue($response->isRedirect(route('admin.login')), "Admin without second factor {$method} {$uri} returned {$response->getStatusCode()}.");
            $this->assertGuest();
        }
    }

    public function test_blocked_anonymized_and_unverified_administrators_cannot_use_administration(): void
    {
        foreach (['blocked' => ['status' => 'blocked', 'blocked_at' => now()], 'anonymized' => ['status' => 'anonymized', 'email' => null, 'username' => null, 'deleted_at' => now()]] as $state => $attributes) {
            $administrator = $this->enrolledAdministrator();
            $blocker = User::factory()->role(UserRole::Administrator)->create();
            DB::table('users')->where('id', $administrator->id)->update([...$attributes, 'blocked_by' => $state === 'blocked' ? $blocker->id : null]);

            $this->actingAsAdministrator($administrator)->get(route('admin.dashboard'))->assertRedirect();
            $this->assertGuest();
        }

        $unverified = User::factory()->role(UserRole::Administrator)->unverified()->create();
        $this->post(route('admin.login.store'), ['email' => $unverified->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['email' => 'This administrator account has not verified its email address.']);
        $this->assertNull(session(AdministratorSession::PENDING_KEY));

        $this->actingAsAdministrator($unverified)->get(route('admin.dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_a_fully_signed_in_administrator_reaches_the_administration_area(): void
    {
        $administrator = $this->enrolledAdministrator();

        $this->actingAsAdministrator($administrator)->get(route('admin.dashboard'))->assertOk();
        $this->actingAsAdministrator($administrator)->get(route('admin.security'))->assertOk()->assertSee('Recovery codes left');
    }

    // ----- Sign-in flow and bypass attempts --------------------------------------------------

    public function test_an_enrolled_administrator_signs_in_with_password_then_authenticator_code(): void
    {
        $administrator = $this->enrolledAdministrator();

        $this->signInWithPassword($administrator)->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertGuest();
        $this->get(route('admin.two-factor.challenge'))->assertOk()->assertSee('Enter your verification code');

        $this->post(route('admin.two-factor.verify'), ['code' => $this->currentTwoFactorCode($administrator)])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($administrator);
        $this->get(route('admin.dashboard'))->assertOk();

        $this->assertDatabaseHas('activity_events', ['actor_user_id' => $administrator->id, 'action' => 'admin.signed_in']);
    }

    public function test_the_password_step_alone_never_opens_the_administration_area(): void
    {
        $administrator = $this->enrolledAdministrator();
        $this->signInWithPassword($administrator);

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('admin.login'));
        $this->get('/account')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_the_second_factor_step_cannot_be_reached_without_the_password(): void
    {
        $administrator = $this->enrolledAdministrator();

        $this->get(route('admin.two-factor.challenge'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.two-factor.setup'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.two-factor.verify'), ['code' => $this->currentTwoFactorCode($administrator)])->assertRedirect(route('admin.login'));
        $this->post(route('admin.two-factor.confirm'), ['code' => '123456'])->assertRedirect(route('admin.login'));

        $this->withSession([AdministratorSession::PENDING_KEY => ['user_id' => $administrator->id, 'expires_at' => now()->subMinute()->getTimestamp()]])
            ->post(route('admin.two-factor.verify'), ['code' => $this->currentTwoFactorCode($administrator)])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_a_pending_sign_in_expires(): void
    {
        $administrator = $this->enrolledAdministrator();
        $this->signInWithPassword($administrator);

        $this->travel(11)->minutes();

        $this->post(route('admin.two-factor.verify'), ['code' => $this->currentTwoFactorCode($administrator)])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors(['email' => 'Your sign-in expired. Enter your email and password again.']);
        $this->assertGuest();
    }

    public function test_administrator_credentials_do_not_work_on_the_homeowner_or_handyman_sign_in(): void
    {
        $administrator = $this->enrolledAdministrator();

        foreach (['login.store', 'handyman.login.store'] as $routeName) {
            $this->post(route($routeName), ['email' => $administrator->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_homeowner_credentials_do_not_work_on_the_administrator_sign_in(): void
    {
        $homeowner = User::factory()->create();

        $this->post(route('admin.login.store'), ['email' => $homeowner->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);
        $this->assertNull(session(AdministratorSession::PENDING_KEY));
        $this->assertGuest();
    }

    public function test_a_remember_me_cookie_cannot_restore_an_administrator_session(): void
    {
        $administrator = $this->enrolledAdministrator();

        $this->post(route('admin.login.store'), ['email' => $administrator->email, 'password' => self::PASSWORD, 'remember' => '1']);
        $response = $this->post(route('admin.two-factor.verify'), ['code' => $this->currentTwoFactorCode($administrator)]);

        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertStringStartsNotWith('remember_web', $cookie->getName(), 'Administrators must never receive a remember-me cookie.');
        }
    }

    public function test_a_wrong_code_is_rejected_and_an_authenticator_code_cannot_be_replayed(): void
    {
        $administrator = $this->enrolledAdministrator();
        $code = $this->currentTwoFactorCode($administrator);
        $wrongCode = $code === '000000' ? '111111' : '000000';

        $this->signInWithPassword($administrator);
        $this->post(route('admin.two-factor.verify'), ['code' => $wrongCode])
            ->assertSessionHasErrors(['code' => 'That code is not valid. Check the time on your phone and try the newest code.']);
        $this->assertGuest();

        $this->post(route('admin.two-factor.verify'), ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));

        $this->signInWithPassword($administrator);
        $this->post(route('admin.two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->assertDatabaseHas('activity_events', ['action' => 'admin.sign_in_failed', 'subject_id' => $administrator->id]);
    }

    public function test_repeated_wrong_codes_lock_the_second_factor_and_require_the_password_again(): void
    {
        $administrator = $this->enrolledAdministrator();
        $this->signInWithPassword($administrator);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('admin.two-factor.verify'), ['code' => '999999'])->assertSessionHasErrors('code');
        }

        $this->post(route('admin.two-factor.verify'), ['code' => $this->currentTwoFactorCode($administrator)])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull(session(AdministratorSession::PENDING_KEY));

        $this->signInWithPassword($administrator);
        $this->post(route('admin.two-factor.verify'), ['code' => $this->currentTwoFactorCode($administrator)])->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_password_guessing_is_rate_limited(): void
    {
        $administrator = $this->enrolledAdministrator();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('admin.login.store'), ['email' => $administrator->email, 'password' => 'wrong-password-'.$attempt])
                ->assertSessionHasErrors(['email' => __('auth.failed')]);
        }

        $this->post(route('admin.login.store'), ['email' => $administrator->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');
        $this->assertNull(session(AdministratorSession::PENDING_KEY));
        $this->assertSame(5, ActivityEvent::query()->where('action', 'admin.sign_in_failed')->where('subject_id', $administrator->id)->count());
    }

    public function test_password_guessing_across_many_accounts_from_one_address_is_rate_limited(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->post(route('admin.login.store'), ['email' => "guess-{$attempt}@example.test", 'password' => 'wrong'])->assertSessionHasErrors('email');
        }

        $this->post(route('admin.login.store'), ['email' => 'guess-11@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'Too many sign-in attempts. Try again in 1 minute(s).']);
    }

    // ----- Recovery codes and enrolment -------------------------------------------------------

    public function test_a_recovery_code_signs_in_once(): void
    {
        $administrator = $this->enrolledAdministrator(['ABCDE-FGHJK', 'LMNPQ-RSTUV']);

        $this->signInWithPassword($administrator);
        $this->post(route('admin.two-factor.verify'), ['code' => 'abcde fghjk'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');
        $this->assertAuthenticatedAs($administrator);
        $this->assertSame(1, $administrator->twoFactorCredential()->first()->remainingRecoveryCodes());
        $this->post(route('logout'));

        $this->signInWithPassword($administrator);
        $this->post(route('admin.two-factor.verify'), ['code' => 'ABCDE-FGHJK'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_new_administrator_enrols_an_authenticator_app_and_receives_recovery_codes_once(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();

        $this->signInWithPassword($administrator)->assertRedirect(route('admin.two-factor.setup'));
        $page = $this->get(route('admin.two-factor.setup'))->assertOk()->assertSee('<svg', false)->assertSee('data-two-factor-secret', false);
        $secret = $administrator->twoFactorCredential()->firstOrFail()->secret;
        $page->assertSee(trim(chunk_split($secret, 4, ' ')));
        $this->assertNull($administrator->twoFactorCredential()->first()->confirmed_at);

        $this->get(route('admin.two-factor.setup'))->assertOk();
        $this->assertSame($secret, $administrator->twoFactorCredential()->first()->secret, 'Reloading the setup page must keep the scanned secret.');

        $this->post(route('admin.two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post(route('admin.two-factor.confirm'), ['code' => (new Google2FA)->getCurrentOtp($secret)])->assertRedirect(route('admin.security'));
        $this->assertAuthenticatedAs($administrator);

        $codes = session('recovery_codes');
        $this->assertCount(8, $codes);
        $this->get(route('admin.security'))->assertOk()->assertSee($codes[0]);
        $this->get(route('admin.security'))->assertOk()->assertDontSee($codes[0]);

        $stored = DB::table('two_factor_credentials')->where('user_id', $administrator->id)->first();
        $this->assertStringNotContainsString($secret, $stored->secret, 'The secret must be encrypted at rest.');
        $this->assertStringNotContainsString($codes[0], $stored->recovery_codes, 'Recovery codes must not be stored in plain text.');
        $this->assertDatabaseHas('activity_events', ['action' => 'admin.two_factor_enabled', 'subject_id' => $administrator->id]);
    }

    public function test_an_enrolled_administrator_cannot_replace_the_second_factor_from_the_sign_in_flow(): void
    {
        $administrator = $this->enrolledAdministrator();
        $secret = $administrator->twoFactorCredential()->first()->secret;

        $this->signInWithPassword($administrator);
        $this->get(route('admin.two-factor.setup'))->assertRedirect(route('admin.two-factor.challenge'));
        $this->post(route('admin.two-factor.confirm'), ['code' => '123456'])->assertRedirect(route('admin.two-factor.challenge'));

        $this->assertSame($secret, $administrator->twoFactorCredential()->first()->secret);
    }

    public function test_recovery_codes_are_regenerated_only_with_the_password(): void
    {
        $administrator = $this->enrolledAdministrator(['OLDOL-DCODE']);

        $this->actingAsAdministrator($administrator)->post(route('admin.security.recovery-codes'), ['password' => 'wrong'])
            ->assertSessionHasErrors(['password' => 'The password is incorrect.']);

        $this->actingAsAdministrator($administrator)->post(route('admin.security.recovery-codes'), ['password' => self::PASSWORD])
            ->assertRedirect(route('admin.security'))
            ->assertSessionHas('recovery_codes');
        $this->assertSame(8, $administrator->twoFactorCredential()->first()->remainingRecoveryCodes());

        $this->post(route('logout'));
        $this->signInWithPassword($administrator);
        $this->post(route('admin.two-factor.verify'), ['code' => 'OLDOL-DCODE'])->assertSessionHasErrors('code');
    }

    // ----- Command-line recovery procedure --------------------------------------------------

    public function test_the_console_reset_lets_an_administrator_enrol_again_with_the_password(): void
    {
        $administrator = $this->enrolledAdministrator();

        $this->artisan('app:admin-two-factor', ['action' => 'status'])->expectsOutputToContain($administrator->email)->assertSuccessful();

        $this->artisan('app:admin-two-factor', ['action' => 'reset', 'email' => strtoupper($administrator->email)])
            ->expectsConfirmation("Remove the second factor of {$administrator->email}? They will enrol a new authenticator app at their next sign-in (password still required).", 'yes')
            ->expectsOutputToContain('Second factor removed.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('two_factor_credentials', ['user_id' => $administrator->id]);
        $this->assertDatabaseHas('activity_events', ['action' => 'admin.two_factor_reset', 'subject_id' => $administrator->id]);
        $this->signInWithPassword($administrator)->assertRedirect(route('admin.two-factor.setup'));

        $this->artisan('app:admin-two-factor', ['action' => 'reset', 'email' => 'nobody@example.test', '--force' => true])->assertFailed();
    }

    public function test_the_path_command_never_prints_the_secret_unless_asked(): void
    {
        $prefix = AdministratorPath::prefix();

        $this->artisan('app:admin-path')
            ->expectsOutput('ADMIN_PATH is configured in the environment.')
            ->doesntExpectOutputToContain($prefix)
            ->assertSuccessful();

        $this->artisan('app:admin-path', ['--show' => true])->expectsOutputToContain($prefix.'/login')->assertSuccessful();

        $this->artisan('app:admin-path', ['--generate' => true])
            ->expectsOutputToContain('already configured')
            ->assertFailed();
    }
}
