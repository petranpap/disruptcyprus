<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('admin:user {email} {--name= : Display name for a new account} {--role=admin : admin or editor}')]
#[Description('Create a staff account, or promote an existing account, for the admin panel')]
class CreateStaffUser extends Command
{
    public function handle(): int
    {
        $role = UserRole::tryFrom((string) $this->option('role'));

        if ($role === null || $role === UserRole::Reader) {
            $this->error('Role must be admin or editor.');

            return self::INVALID;
        }

        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            $user->forceFill(['role' => $role])->save();
            $this->info("{$email} is now {$role->value}.");

            return self::SUCCESS;
        }

        $password = (string) ($this->secret('Password (leave empty to generate one)') ?: Str::password(16));

        User::query()->create([
            'name' => (string) ($this->option('name') ?: strstr($email, '@', true)),
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'email_verified_at' => now(),
            'consent_at' => now(),
            'consent_version' => config('app.consent_version'),
            'onboarded_at' => now(),
        ]);

        $this->info("Created {$role->value} {$email}.");
        $this->line("Password: {$password}");

        return self::SUCCESS;
    }
}
