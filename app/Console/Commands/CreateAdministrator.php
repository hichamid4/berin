<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-administrator')]
#[Description('Create a verified administrator account from the command line.')]
class CreateAdministrator extends Command
{
    public function handle(): int
    {
        $data = [
            'first_name' => $this->ask('First name'),
            'last_name' => $this->ask('Last name'),
            'username' => $this->ask('Username'),
            'email' => mb_strtolower((string) $this->ask('Email')),
        ];

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:40', 'unique:users,username'],
            'email' => ['required', 'email:rfc', 'max:254', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = $this->secret('Password (minimum 12 characters)');
        if (strlen((string) $password) < 12 || $password !== $this->secret('Confirm password')) {
            $this->error('The password must match and be at least 12 characters.');

            return self::FAILURE;
        }

        $user = User::create([
            ...$data,
            'role' => UserRole::Administrator,
            'password' => $password,
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $this->info("Administrator account created (user ID {$user->id}).");

        return self::SUCCESS;
    }
}
