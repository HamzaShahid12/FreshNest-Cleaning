<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? $request->query('month') : now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth();

        $bookings = Booking::with(['service', 'employee'])
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->orderBy('scheduled_at')->get();

        return Inertia::render('Admin/Calendar', [
            'month' => $start->format('Y-m'),
            'label' => $start->format('F Y'),
            'prev' => $start->copy()->subMonth()->format('Y-m'),
            'next' => $start->copy()->addMonth()->format('Y-m'),
            'firstWeekday' => (int) $start->isoFormat('E'), // 1 = Monday
            'daysInMonth' => $start->daysInMonth,
            'today' => now()->format('Y-m-d'),
            'events' => $bookings->map(fn ($b) => [
                'reference' => $b->reference, 'date' => $b->scheduled_at->format('Y-m-d'), 'time' => $b->scheduled_at->format('g:i A'),
                'service' => $b->service?->name, 'customer' => $b->contact_name, 'status' => $b->status, 'employee' => $b->employee?->name,
            ]),
        ]);
    }
}
