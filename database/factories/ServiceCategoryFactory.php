<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceCategory> */
class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return ['name' => ucfirst($name), 'slug' => $name.fake()->unique()->numerify('##'), 'is_active' => true];
    }
}
