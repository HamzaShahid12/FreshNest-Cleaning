<?php

namespace Database\Factories;

use App\Models\ServiceExtra;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceExtra> */
class ServiceExtraFactory extends Factory
{
    protected $model = ServiceExtra::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'price' => 20, 'duration_minutes' => 30, 'is_active' => true];
    }
}
