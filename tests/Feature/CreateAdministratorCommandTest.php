<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CreateAdministratorCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cli_created_administrator_is_verified_and_can_sign_in_to_admin_dashboard(): void
    {
        $this->artisan('app:create-administrator')
            ->expectsQuestion('First name', 'Amina')
            ->expectsQuestion('Last name', 'Administrator')
            ->expectsQuestion('Username', 'platform-admin')
            ->expectsQuestion('Email', 'admin@example.test')
            ->expectsQuestion('Password (minimum 8 characters)', 'Secure-admin-password-2026!')
            ->expectsQuestion('Confirm password', 'Secure-admin-password-2026!')
            ->assertExitCode(0);

        $administrator = User::query()->where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame(UserRole::Administrator, $administrator->role);
        $this->assertSame('active', $administrator->status);
        $this->assertNotNull($administrator->email_verified_at);

        $this->post(route('admin.login.store'), [
            'email' => 'admin@example.test',
            'password' => 'Secure-admin-password-2026!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk();
    }
}
