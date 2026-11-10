<?php

namespace App\Console\Commands;

use App\Actions\Accounts\AdministratorTwoFactor;
use App\Models\User;
use App\UserRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:admin-two-factor
    {action : "status" lists administrators and their second factor, "reset" removes one so it can be enrolled again}
    {email? : Administrator email (required for reset)}
    {--force : Reset without asking for confirmation}')]
#[Description('Recovery tool for administrator two-factor authentication. Run only from a trusted terminal.')]
class AdministratorTwoFactorCommand extends Command
{
    public function handle(AdministratorTwoFactor $twoFactor): int
    {
        return match ($this->argument('action')) {
            'status' => $this->status(),
            'reset' => $this->reset($twoFactor),
            default => $this->invalidAction(),
        };
    }

    private function status(): int
    {
        $administrators = User::query()->where('role', UserRole::Administrator)->with('twoFactorCredential')->orderBy('id')->get();

        $this->table(['ID', 'Email', 'Status', 'Second factor', 'Recovery codes left'], $administrators->map(fn (User $administrator): array => [
            $administrator->id,
            $administrator->email,
            $administrator->status,
            match (true) {
                $administrator->twoFactorCredential?->isConfirmed() => 'active since '.$administrator->twoFactorCredential->confirmed_at->format('Y-m-d'),
                $administrator->twoFactorCredential !== null => 'enrolment started, not confirmed',
                default => 'not enrolled (enrols at next sign-in)',
            },
            $administrator->twoFactorCredential?->isConfirmed() ? $administrator->twoFactorCredential->remainingRecoveryCodes() : '—',
        ])->all());

        return self::SUCCESS;
    }

    private function reset(AdministratorTwoFactor $twoFactor): int
    {
        $email = mb_strtolower((string) $this->argument('email'));
        $administrator = User::query()->where('role', UserRole::Administrator)->where('email', $email)->first();

        if ($administrator === null) {
            $this->error('No administrator account uses that email address.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Remove the second factor of {$administrator->email}? They will enrol a new authenticator app at their next sign-in (password still required).")) {
            $this->line('Nothing changed.');

            return self::SUCCESS;
        }

        if ($twoFactor->reset($administrator)) {
            $this->info('Second factor removed. At the next sign-in the administrator enters their password and scans a new QR code.');
        } else {
            $this->line('This administrator had no second factor; nothing to reset.');
        }

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->error('Unknown action. Use "status" or "reset".');

        return self::FAILURE;
    }
}
