<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Booking> */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $start = now()->addDays(5)->setTime(10, 0);

        return [
            'reference' => Booking::generateReference(),
            'user_id' => User::factory()->customer(),
            'service_id' => Service::factory(),
            'property_type' => 'apartment', 'bedrooms' => 1, 'bathrooms' => 1, 'frequency' => 'one_time',
            'scheduled_at' => $start, 'ends_at' => $start->copy()->addHours(2), 'duration_minutes' => 120,
            'street' => fake()->streetAddress(), 'city' => 'Austin', 'state' => 'TX', 'zip' => '78701',
            'contact_name' => fake()->name(), 'contact_email' => fake()->safeEmail(), 'contact_phone' => '(555) 010-0000',
            'subtotal' => 80, 'discount_total' => 0, 'tax' => 0, 'total' => 80,
            'status' => 'pending', 'payment_method' => 'pay_later', 'payment_status' => 'unpaid',
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
