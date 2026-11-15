<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the Super Admin.
 *
 *  - ADMIN_SEED_PASSWORD set in .env  -> that password is used.
 *  - APP_ENV=local/testing and nothing set -> the documented DEVELOPMENT-ONLY password is used.
 *  - Any other environment and nothing set -> a random password is generated and printed ONCE.
 *
 * In every case the account must change its password at first sign-in.
 */
class UserSeeder extends Seeder
{
    public const DEV_PASSWORD = 'ChangeMe!Dev2026';

    public function run(): void
    {
        $email = env('ADMIN_SEED_EMAIL', 'admin@freshnest.test');
        $configured = env('ADMIN_SEED_PASSWORD');
        $local = app()->environment(['local', 'testing']);

        $password = $configured ?: ($local ? self::DEV_PASSWORD : Str::password(18));
        $generated = ! $configured && ! $local;

        $admin = User::where('email', $email)->first() ?? new User;
        if (! $admin->exists) {
            $admin->fill(['name' => 'FreshNest Super Admin', 'email' => $email, 'password' => $password, 'phone' => null]);
            $admin->forceFill(['role' => 'super_admin', 'is_active' => true, 'must_change_password' => true, 'email_verified_at' => now()])->save();

            if ($generated && $this->command) {
                $this->command->warn("Super Admin created: {$email}");
                $this->command->warn("Generated password (shown once, change it at first login): {$password}");
            }
        }
    }
}
