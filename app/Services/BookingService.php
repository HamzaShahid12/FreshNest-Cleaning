<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingExtra;
use App\Models\BookingStatusHistory;
use App\Models\EmployeeProfile;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private PricingService $pricing,
        private AvailabilityService $availability,
    ) {}

    /**
     * Create a booking. Price, duration and availability are all computed/validated here on the server.
     *
     * @param  bool  $force   skip availability + notice rules (used by the demo seeder only)
     * @param  bool  $notify  send e-mails
     */
    public function create(array $data, User $user, bool $force = false, bool $notify = true): Booking
    {
        // Duplicate submission protection: the same idempotency key returns the same booking.
        if (! empty($data['idempotency_key'])) {
            $existing = Booking::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if ($existing->user_id !== $user->id) {
                    throw ValidationException::withMessages(['idempotency_key' => 'Invalid request.']);
                }

                return $existing;
            }
        }

        $quote = $this->pricing->quote($data);
        if ($quote['coupon_error'] && ! empty($data['coupon_code'])) {
            throw ValidationException::withMessages(['coupon_code' => $quote['coupon_error']]);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['time']);
        $duration = $quote['duration_minutes'];
        $end = $start->copy()->addMinutes($duration);

        return DB::transaction(function () use ($data, $user, $force, $notify, $quote, $start, $end, $duration) {
            if (! $force) {
                // Serialise concurrent bookings by locking the staff rows while we check capacity.
                EmployeeProfile::query()->lockForUpdate()->pluck('id');

                if (! $this->availability->isSlotFree($start, $duration)) {
                    throw ValidationException::withMessages([
                        'time' => 'That time is no longer available. Please choose another slot.',
                    ]);
                }
            }

            $method = $quote['requires_quote'] ? 'pay_later' : ($data['payment_method'] ?? 'pay_later');

            $booking = Booking::create([
                'reference' => Booking::generateReference(),
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'user_id' => $user->id,
                'service_id' => $quote['service']->id,
                'coupon_id' => $quote['coupon_id'],
                'coupon_code' => $quote['coupon_code'],
                'property_type' => $data['property_type'],
                'bedrooms' => (int) ($data['bedrooms'] ?? 1),
                'bathrooms' => (int) ($data['bathrooms'] ?? 1),
                'sqft' => $data['sqft'] ?? null,
                'frequency' => $data['frequency'] ?? 'one_time',
                'scheduled_at' => $start,
                'ends_at' => $end,
                'duration_minutes' => $duration,
                'street' => $data['street'],
                'unit' => $data['unit'] ?? null,
                'city' => $data['city'],
                'state' => $data['state'] ?? null,
                'zip' => $data['zip'] ?? null,
                'country' => $data['country'] ?? null,
                'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $quote['subtotal'],
                'discount_total' => $quote['discount_total'],
                'tax' => $quote['tax'],
                'total' => $quote['total'],
                'requires_quote' => $quote['requires_quote'],
                'status' => 'pending',
                'payment_method' => $method,
                'payment_status' => 'unpaid',
            ]);

            foreach ($quote['extras'] as $extra) {
                BookingExtra::create([
                    'booking_id' => $booking->id,
                    'service_extra_id' => $extra->id,
                    'name' => $extra->name,
                    'price' => $extra->price,
                    'quantity' => 1,
                ]);
            }

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'changed_by' => $user->id,
                'from_status' => null,
                'to_status' => 'pending',
                'note' => 'Booking requested',
            ]);

            Invoice::create([
                'booking_id' => $booking->id,
                'number' => 'INV-'.$start->format('Y').'-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
                'issued_on' => now()->toDateString(),
                'due_on' => $start->toDateString(),
                'subtotal' => $booking->subtotal,
                'discount' => $booking->discount_total,
                'tax' => $booking->tax,
                'total' => $booking->total,
                'status' => 'unpaid',
            ]);

            Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'provider' => $method === 'stripe' ? 'stripe' : 'offline',
                'method' => $method,
                'amount' => $booking->total,
                'currency' => 'USD',
                'status' => 'pending',
            ]);

            if ($quote['coupon_id']) {
                DB::table('coupons')->where('id', $quote['coupon_id'])->increment('used_count');
            }

            if ($notify) {
                Notify::user($user, 'booking_received', Notify::bookingVars($booking), route('account.bookings.show', $booking));
                Notify::admins('admin_new_booking', Notify::bookingVars($booking), route('admin.bookings.show', $booking));
            }

            return $booking;
        });
    }

    /** Move a booking to a new status, enforcing the allowed transitions. */
    public function transition(Booking $booking, string $to, ?User $actor = null, ?string $note = null): Booking
    {
        $from = $booking->status;
        if ($from === $to) {
            return $booking;
        }

        if (! in_array($to, config("freshnest.transitions.{$from}", []), true)) {
            throw ValidationException::withMessages(['status' => "A {$from} booking cannot be changed to {$to}."]);
        }

        if ($to === 'in_progress' && ! $booking->employee_id) {
            throw ValidationException::withMessages(['status' => 'Assign a cleaner before starting the job.']);
        }

        $booking->status = $to;
        if ($to === 'completed') {
            $booking->completed_at = now();
        }
        if (in_array($to, ['cancelled', 'rejected'], true)) {
            $booking->cancelled_at = now();
            $booking->cancel_reason = $note;
        }
        $booking->save();

        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'changed_by' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);

        if ($to === 'completed' && $booking->payment_method === 'cash') {
            // Cash is collected at the door; an admin records the payment. We do not mark it paid automatically.
        }

        AuditLog::record('booking.status', $booking, "{$booking->reference}: {$from} -> {$to}", ['from' => $from, 'to' => $to]);

        $vars = Notify::bookingVars($booking);
        $url = $booking->user_id ? route('account.bookings.show', $booking) : null;
        $customer = $booking->user;
        match ($to) {
            'confirmed' => Notify::user($customer, 'booking_confirmed', $vars, $url),
            'cancelled' => Notify::user($customer, 'booking_cancelled', $vars, $url),
            'rejected' => Notify::user($customer, 'booking_rejected', $vars, $url),
            'completed' => (function () use ($customer, $vars, $url) {
                Notify::user($customer, 'booking_completed', $vars, $url);
                Notify::user($customer, 'review_request', $vars, $url);
            })(),
            default => null,
        };

        return $booking->refresh();
    }

    /** Assign a cleaner. Overlapping jobs for the same cleaner are refused. */
    public function assign(Booking $booking, User $employee, ?User $actor = null): Booking
    {
        if (! in_array($booking->status, ['confirmed', 'assigned'], true)) {
            throw ValidationException::withMessages(['employee_id' => 'Confirm the booking before assigning a cleaner.']);
        }
        if ($employee->role !== 'staff' || ! $employee->is_active) {
            throw ValidationException::withMessages(['employee_id' => 'Choose an active cleaning staff member.']);
        }
        if (! $this->availability->employeeIsFree($employee, $booking->scheduled_at, $booking->ends_at, $booking->id)) {
            throw ValidationException::withMessages(['employee_id' => "{$employee->name} already has a job at that time."]);
        }

        $from = $booking->status;
        $booking->employee_id = $employee->id;
        $booking->status = 'assigned';
        $booking->save();

        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'changed_by' => $actor?->id,
            'from_status' => $from,
            'to_status' => 'assigned',
            'note' => "Assigned to {$employee->name}",
        ]);
        AuditLog::record('booking.assign', $booking, "{$booking->reference} assigned to {$employee->name}");

        $vars = Notify::bookingVars($booking->refresh());
        Notify::user($booking->user, 'employee_assigned', $vars, $booking->user_id ? route('account.bookings.show', $booking) : null);
        Notify::user($employee, 'job_assigned', $vars, route('staff.jobs.show', $booking));

        return $booking;
    }

    /**
     * Reschedule. Customers are bound by the cancellation window; admins can override notice rules
     * but never create an overlap beyond cleaner capacity.
     */
    public function reschedule(Booking $booking, string $date, string $time, ?User $actor = null, bool $byCustomer = false): Booking
    {
        if (! in_array($booking->status, ['pending', 'confirmed', 'assigned'], true)) {
            throw ValidationException::withMessages(['date' => 'This booking can no longer be rescheduled.']);
        }
        if ($byCustomer && ! $booking->customerCanModify()) {
            throw ValidationException::withMessages(['date' => 'It is too close to the appointment to reschedule online. Please contact us.']);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}");
        $end = $start->copy()->addMinutes($booking->duration_minutes);

        return DB::transaction(function () use ($booking, $start, $end, $actor, $byCustomer) {
            EmployeeProfile::query()->lockForUpdate()->pluck('id');

            if (! $this->availability->isSlotFree($start, $booking->duration_minutes, $booking->id, $byCustomer)) {
                throw ValidationException::withMessages(['time' => 'That time is not available. Please choose another slot.']);
            }

            $old = $booking->scheduled_at->format('M j, g:i A');
            $booking->scheduled_at = $start;
            $booking->ends_at = $end;

            $note = "Rescheduled from {$old}";
            if ($booking->employee_id && ! $this->availability->employeeIsFree($booking->employee, $start, $end, $booking->id)) {
                $booking->employee_id = null;
                if ($booking->status === 'assigned') {
                    $booking->status = 'confirmed';
                }
                $note .= ' (cleaner unassigned: not free at the new time)';
            }
            $booking->save();

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'changed_by' => $actor?->id,
                'from_status' => $booking->status,
                'to_status' => $booking->status,
                'note' => $note,
            ]);
            AuditLog::record('booking.reschedule', $booking, "{$booking->reference} {$note}");

            Notify::user($booking->user, 'booking_rescheduled', Notify::bookingVars($booking), route('account.bookings.show', $booking));
            if ($byCustomer) {
                Notify::admins('booking_rescheduled', Notify::bookingVars($booking), route('admin.bookings.show', $booking));
            }

            return $booking->refresh();
        });
    }

    /** Record a successful payment (called from the Stripe webhook or by an admin for cash/offline). */
    public function recordPayment(Payment $payment, ?string $reference = null, ?string $paymentIntent = null): void
    {
        if ($payment->status === 'paid') {
            return; // idempotent
        }

        DB::transaction(function () use ($payment, $reference, $paymentIntent) {
            $payment->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'transaction_reference' => $reference ?? $payment->transaction_reference,
                'payment_intent_id' => $paymentIntent ?? $payment->payment_intent_id,
            ])->save();

            $booking = $payment->booking;
            $booking->payment_status = 'paid';
            $booking->save();

            Invoice::where('booking_id', $booking->id)->update(['status' => 'paid', 'paid_at' => now()]);
        });

        $booking = $payment->booking->refresh();
        Notify::user($booking->user, 'payment_confirmed', Notify::bookingVars($booking), $booking->user_id ? route('account.bookings.show', $booking) : null);
        AuditLog::record('payment.paid', $payment, "Payment for {$booking->reference} marked paid");
    }
}
