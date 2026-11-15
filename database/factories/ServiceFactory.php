<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true).' cleaning';

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'base_price' => 80,
            'pricing_method' => 'size_based',
            'duration_minutes' => 120,
            'is_active' => true,
            'sort_order' => 0,
            'included' => ['Dusting', 'Vacuuming', 'Mopping'],
        ];
    }

    public function flat(float $price = 60): static
    {
        return $this->state(fn () => ['pricing_method' => 'flat', 'base_price' => $price]);
    }

    public function quote(): static
    {
        return $this->state(fn () => ['pricing_method' => 'quote', 'base_price' => 0]);
    }
}
