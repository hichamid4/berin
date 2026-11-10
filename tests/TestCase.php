<?php

namespace Tests;

use App\Actions\Accounts\AdministratorSession;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PragmaRX\Google2FA\Google2FA;

abstract class TestCase extends BaseTestCase
{
    /**
     * Act as an administrator whose session completed the two-step sign-in (password and second
     * factor). Plain actingAs() on an administrator is refused by EnsureAdministratorTwoFactor.
     */
    protected function actingAsAdministrator(User $administrator): static
    {
        return $this->actingAs($administrator)->withSession([AdministratorSession::PASSED_KEY => $administrator->id]);
    }

    /**
     * The code an authenticator app would show right now for this administrator's secret.
     */
    protected function currentTwoFactorCode(User $administrator): string
    {
        return (new Google2FA)->getCurrentOtp($administrator->twoFactorCredential()->firstOrFail()->secret);
    }

    /**
     * Refuse to run database-resetting tests against any database that is not a dedicated
     * "*_testing" database, so the local demonstration database can never be wiped.
     * Tests that use DatabaseTransactions only roll back their own changes and remain allowed.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits(): array
    {
        $uses = class_uses_recursive(static::class);
        $resetsDatabase = isset($uses[RefreshDatabase::class]) || isset($uses[LazilyRefreshDatabase::class]);
        $database = (string) config('database.connections.'.config('database.default').'.database');

        if ($resetsDatabase && ! str_ends_with($database, '_testing') && $database !== ':memory:') {
            $this->markTestSkipped("Skipped: this test resets the database and \"{$database}\" is not a *_testing database.");
        }

        return parent::setUpTraits();
    }
}
