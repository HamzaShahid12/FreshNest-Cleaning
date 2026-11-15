<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(###) ###-####'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123'),
            'remember_token' => \Illuminate\Support\Str::random(10),
        ];
    }

    /** Role / active flags are not mass assignable, so they are applied after creation. */
    public function role(string $role): static
    {
        return $this->afterMaking(fn (User $u) => $u->forceFill(['role' => $role, 'is_active' => true]))
            ->afterCreating(function (User $u) use ($role) {
                if ($role === 'staff') {
                    $u->employeeProfile()->firstOrCreate([], ['working_days' => [1, 2, 3, 4, 5, 6, 7], 'work_start' => '07:00', 'work_end' => '20:00']);
                }
            });
    }

    public function customer(): static
    {
        return $this->role('customer');
    }

    public function staff(): static
    {
        return $this->role('staff');
    }

    public function admin(): static
    {
        return $this->role('admin');
    }

    public function manager(): static
    {
        return $this->role('manager');
    }

    public function superAdmin(): static
    {
        return $this->role('super_admin');
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
