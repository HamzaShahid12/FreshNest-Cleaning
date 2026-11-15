<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceExtra;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Local-only demo accounts and bookings. Never runs outside local/testing environments. */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = UserSeeder::DEV_PASSWORD;
        $svc = app(BookingService::class);

        $make = function (string $name, string $email, string $role) use ($password) {
            $u = User::where('email', $email)->first();
            if (! $u) {
                $u = new User(['name' => $name, 'email' => $email, 'password' => $password, 'phone' => '(555) 010-'.rand(1000, 9999)]);
                $u->forceFill(['role' => $role, 'is_active' => true, 'email_verified_at' => now()])->save();
            }

            return $u;
        };

        $make('Morgan Admin', 'administrator@freshnest.test', 'admin');
        $make('Mia Manager', 'manager@freshnest.test', 'manager');

        $staff = collect([['Sofia Ramirez', 'sofia@freshnest.test'], ['James Carter', 'james@freshnest.test'], ['Aisha Khan', 'aisha@freshnest.test']])
            ->map(function ($row) use ($make) {
                $u = $make($row[0], $row[1], 'staff');
                $u->employeeProfile()->firstOrCreate([], [
                    'job_title' => 'Cleaning Specialist', 'working_days' => [1, 2, 3, 4, 5, 6], 'work_start' => '07:00', 'work_end' => '18:00', 'is_available' => true, 'hired_on' => now()->subMonths(rand(2, 30)),
                ]);

                return $u;
            });

        $customers = collect([['Emily Johnson', 'customer@freshnest.test'], ['Daniel Brooks', 'daniel@example.test'], ['Priya Patel', 'priya@example.test'], ['Marcus Lee', 'marcus@example.test']])
            ->map(fn ($r) => $make($r[0], $r[1], 'customer'));

        if (Booking::exists()) {
            return;
        }

        $services = Service::where('pricing_method', '!=', 'quote')->get();
        $extras = ServiceExtra::pluck('id');
        $statusPlan = ['completed', 'completed', 'completed', 'completed', 'completed', 'completed', 'cancelled', 'pending', 'confirmed', 'assigned', 'pending', 'confirmed'];

        foreach ($statusPlan as $i => $status) {
            $customer = $customers[$i % $customers->count()];
            $past = $status === 'completed' || $status === 'cancelled';
            $day = $past ? now()->subDays(3 + $i * 6) : now()->addDays(1 + $i % 6);
            $service = $services[$i % $services->count()];

            $booking = $svc->create([
                'service_id' => $service->id, 'property_type' => ['apartment', 'house'][$i % 2],
                'bedrooms' => 1 + $i % 4, 'bathrooms' => 1 + $i % 3, 'sqft' => 700 + ($i * 180),
                'frequency' => ['one_time', 'weekly', 'biweekly', 'monthly'][$i % 4],
                'extras' => $i % 3 === 0 ? [$extras[0], $extras[1]] : [],
                'street' => (100 + $i * 7).' Oak Street', 'city' => 'Austin', 'state' => 'TX', 'zip' => '78701',
                'date' => $day->format('Y-m-d'), 'time' => sprintf('%02d:00', 8 + ($i % 4) * 2),
                'contact_name' => $customer->name, 'contact_email' => $customer->email, 'contact_phone' => $customer->phone,
                'payment_method' => ['pay_later', 'cash'][$i % 2],
                'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            ], $customer, force: true, notify: false);

            if (in_array($status, ['confirmed', 'assigned', 'completed'], true)) {
                $svc->transition($booking, 'confirmed', null, 'Confirmed', );
            }
            if (in_array($status, ['assigned', 'completed'], true)) {
                $emp = $staff[$i % $staff->count()];
                if (app(\App\Services\AvailabilityService::class)->employeeIsFree($emp, $booking->scheduled_at, $booking->ends_at, $booking->id)) {
                    $svc->assign($booking->refresh(), $emp, null);
                }
            }
            if ($status === 'completed') {
                $booking->refresh();
                if ($booking->employee_id) {
                    $svc->transition($booking, 'in_progress');
                    $svc->transition($booking->refresh(), 'completed', null, 'Completed');
                    $payment = $booking->payments()->first();
                    if ($payment) {
                        $svc->recordPayment($payment, 'DEMO-'.$booking->reference);
                    }
                    Review::updateOrCreate(['booking_id' => $booking->id], [
                        'user_id' => $customer->id, 'service_id' => $booking->service_id, 'rating' => [5, 5, 4, 5][$i % 4],
                        'comment' => ['Spotless work, friendly and on time!', 'My apartment has never looked better.', 'Great attention to detail. Will book again.', 'Booking was easy and the team was fantastic.'][$i % 4],
                        'is_approved' => true,
                    ]);
                }
            }
            if ($status === 'cancelled') {
                $svc->transition($booking, 'cancelled', null, 'Customer changed plans');
            }
        }
    }
}
