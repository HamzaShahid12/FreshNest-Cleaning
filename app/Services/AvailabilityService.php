<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EmployeeProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Dynamic availability.
 *
 * A time slot is open when the number of bookings already occupying it is lower than
 * the number of cleaners working at that time. Individual cleaner overlap is enforced
 * again when a manager assigns a job (see employeeIsFree()).
 */
class AvailabilityService
{
    /**
     * @return array<int, array{time:string,label:string,available:bool}>
     */
    public function slotsFor(Carbon $date, int $durationMinutes, ?int $excludeBookingId = null): array
    {
        $interval = max(15, Setting::int('slot_interval_minutes', 60));
        $first = $this->clock($date, (string) Setting::get('slot_start', '08:00'));
        $last = $this->clock($date, (string) Setting::get('slot_end', '16:00'));

        $slots = [];
        for ($t = $first->copy(); $t->lte($last); $t->addMinutes($interval)) {
            $slots[] = [
                'time' => $t->format('H:i'),
                'label' => $t->format('g:i A'),
                'available' => $this->isSlotFree($t->copy(), $durationMinutes, $excludeBookingId),
            ];
        }

        return $slots;
    }

    public function isSlotFree(Carbon $start, int $durationMinutes, ?int $excludeBookingId = null, bool $respectNotice = true): bool
    {
        $end = $start->copy()->addMinutes($durationMinutes);

        if ($respectNotice) {
            if ($start->lt(now()->addHours(Setting::int('min_notice_hours', 12)))) {
                return false;
            }
            if ($start->gt(now()->addDays(Setting::int('max_advance_days', 60))->endOfDay())) {
                return false;
            }
        }

        // The whole job must finish on the same day.
        if (! $start->isSameDay($end)) {
            return false;
        }

        $capacity = $this->workersAt($start, $end)->count();
        if ($capacity === 0) {
            return false;
        }

        $busy = Booking::active()
            ->overlapping($start, $end)
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->count();

        return $busy < $capacity;
    }

    /** Active cleaners whose working pattern covers the whole window. */
    public function workersAt(Carbon $start, Carbon $end): Collection
    {
        $dow = (int) $start->isoFormat('E'); // 1 (Mon) - 7 (Sun)

        return EmployeeProfile::query()
            ->where('is_available', true)
            ->whereHas('user', fn ($q) => $q->where('is_active', true)->where('role', 'staff'))
            ->get()
            ->filter(function (EmployeeProfile $p) use ($dow, $start, $end) {
                $days = array_map('intval', $p->working_days ?? [1, 2, 3, 4, 5]);

                return in_array($dow, $days, true)
                    && $p->work_start <= $start->format('H:i')
                    && $p->work_end >= $end->format('H:i');
            })
            ->values();
    }

    /** True when the given staff member has no other active job overlapping the window. */
    public function employeeIsFree(User $employee, Carbon $start, Carbon $end, ?int $excludeBookingId = null): bool
    {
        return ! Booking::active()
            ->where('employee_id', $employee->id)
            ->overlapping($start, $end)
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->exists();
    }

    private function clock(Carbon $date, string $hhmm): Carbon
    {
        [$h, $m] = array_pad(explode(':', $hhmm), 2, '00');

        return $date->copy()->setTime((int) $h, (int) $m, 0);
    }
}
