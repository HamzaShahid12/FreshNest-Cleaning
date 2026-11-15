<?php

namespace Database\Factories;

use App\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeProfile> */
class EmployeeProfileFactory extends Factory
{
    protected $model = EmployeeProfile::class;

    public function definition(): array
    {
        return ['job_title' => 'Cleaning Specialist', 'working_days' => [1, 2, 3, 4, 5], 'work_start' => '08:00', 'work_end' => '17:00', 'is_available' => true];
    }
}
