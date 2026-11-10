<?php

namespace App\Console\Commands;

use App\Actions\Accounts\AdministratorPath;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:admin-path
    {--generate : Write a new random ADMIN_PATH to the local .env file}
    {--force : With --generate, replace an existing ADMIN_PATH}
    {--show : Print the private administrator sign-in URL}')]
#[Description('Manage the private administration path. Without --show the secret path is never printed.')]
class AdministratorPathCommand extends Command
{
    public function handle(): int
    {
        if ($this->option('generate')) {
            return $this->generate();
        }

        if (AdministratorPath::isConfigured()) {
            $this->info('ADMIN_PATH is configured in the environment.');
        } else {
            $this->warn('ADMIN_PATH is not set (or shorter than '.AdministratorPath::MIN_LENGTH.' characters). A path derived from APP_KEY is used.');
            $this->line('Run "php artisan app:admin-path --generate" to set an explicit random path.');
        }

        if ($this->option('show')) {
            $this->line('Administrator sign-in: '.url(AdministratorPath::prefix().'/login'));
            $this->warn('Keep this address private. It is not a password: sign-in still requires your password and authenticator code.');
        }

        return self::SUCCESS;
    }

    private function generate(): int
    {
        if (AdministratorPath::isConfigured() && ! $this->option('force')) {
            $this->error('ADMIN_PATH is already configured. Use --force to replace it (bookmarks to the old address will stop working).');

            return self::FAILURE;
        }

        $environmentFile = $this->laravel->environmentFilePath();

        if (! is_file($environmentFile) || ! is_writable($environmentFile)) {
            $this->error('The .env file is missing or not writable.');

            return self::FAILURE;
        }

        $path = AdministratorPath::generate();
        $contents = (string) file_get_contents($environmentFile);
        $line = 'ADMIN_PATH='.$path;

        $contents = preg_match('/^ADMIN_PATH=.*$/m', $contents) === 1
            ? (string) preg_replace('/^ADMIN_PATH=.*$/m', $line, $contents)
            : rtrim($contents, "\r\n")."\n\n# Private administration path (secret; never commit).\n".$line."\n";

        file_put_contents($environmentFile, $contents);

        $this->info('A new random ADMIN_PATH was written to .env.');
        $this->line('Run "php artisan config:clear" (and "php artisan route:clear" if routes are cached), then restart the server.');

        if ($this->option('show')) {
            $this->line('Administrator sign-in: '.url($path.'/login'));
        } else {
            $this->line('Use "php artisan app:admin-path --show" to display the new address.');
        }

        return self::SUCCESS;
    }
}
