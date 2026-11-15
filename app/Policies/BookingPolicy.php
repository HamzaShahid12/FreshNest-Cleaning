<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Customers see only their own bookings; cleaners see only jobs assigned to them;
 * admins/managers see everything.
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdminRole()
            || $booking->user_id === $user->id
            || ($user->isStaff() && $booking->employee_id === $user->id);
    }

    public function viewAsCustomer(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    /** Customer-facing details of the job (name/phone/address) for the assigned cleaner. */
    public function viewCustomerDetails(User $user, Booking $booking): bool
    {
        if ($user->isAdminRole()) {
            return true;
        }

        return $user->isStaff()
            && $booking->employee_id === $user->id
            && in_array($booking->status, ['assigned', 'in_progress', 'completed'], true);
    }

    public function manage(User $user, Booking $booking): bool
    {
        return $user->isAdminRole();
    }

    public function work(User $user, Booking $booking): bool
    {
        return $user->isStaff() && $booking->employee_id === $user->id;
    }
}
