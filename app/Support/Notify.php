<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\TemplatedNotification;

/**
 * Sends template-based notifications. A mail failure must never break a booking,
 * so errors are reported to the log and swallowed.
 */
class Notify
{
    public static function user(?User $user, string $event, array $vars = [], ?string $url = null): void
    {
        if (! $user) {
            return;
        }

        try {
            $user->notify(new TemplatedNotification($event, $vars, $url));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function admins(string $event, array $vars = [], ?string $url = null): void
    {
        User::whereIn('role', config('freshnest.admin_roles'))->where('is_active', true)->get()
            ->each(fn (User $u) => static::user($u, $event, $vars, $url));
    }

    /** Placeholder values for booking-related templates. */
    public static function bookingVars(Booking $b): array
    {
        $b->loadMissing(['service', 'employee']);

        return [
            'name' => $b->contact_name,
            'reference' => $b->reference,
            'service' => $b->service?->name,
            'date' => $b->scheduled_at->format('l, F j, Y'),
            'time' => $b->scheduled_at->format('g:i A'),
            'total' => $b->requires_quote ? 'Custom quote' : '$'.number_format((float) $b->total, 2),
            'employee' => $b->employee?->name ?? 'Your cleaner',
            'business' => Setting::get('business_name'),
        ];
    }
}
