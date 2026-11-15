<?php

namespace App\Support;

use App\Models\User;

class Home
{
    /** Where a user lands after login, by role. */
    public static function pathFor(?User $user): string
    {
        if (! $user) {
            return '/';
        }

        return match (true) {
            in_array($user->role, config('freshnest.admin_roles'), true) => '/admin',
            $user->role === 'staff' => '/staff',
            default => '/account',
        };
    }
}
